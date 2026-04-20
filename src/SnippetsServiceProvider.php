<?php

namespace VelaBuild\Snippets;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * SnippetsServiceProvider — reference implementation for Vela plugins.
 *
 * Read this file top to bottom. It demonstrates every extension point a
 * typical plugin touches. The full plugin-authoring guide lives in core
 * at `docs/plugins.md`; this file is the working example the guide points
 * to.
 *
 * ─────────────────────────────────────────────────────────────────────
 * What this plugin adds (for orientation before reading the code)
 * ─────────────────────────────────────────────────────────────────────
 *   1. A `vela_snippets` DB table (migration auto-runs)
 *   2. An admin page at /admin/snippets (CRUD + live preview iframe)
 *   3. A sidebar menu entry under "Content → Snippets"
 *   4. A `Snippet` block type in the Page Builder block picker
 *   5. Four permissions (access/create/edit/delete) wired into vela_permissions
 *
 * ─────────────────────────────────────────────────────────────────────
 * Plugin lifecycle — the four things every plugin does
 * ─────────────────────────────────────────────────────────────────────
 *   1. loadMigrations / loadViews / route group (middleware-wrapped)
 *   2. Register with Vela's registries (blocks, menus, templates, …)
 *   3. Seed permissions into vela_permissions (core's VelaAuthGates
 *      middleware picks them up on every admin request)
 *   4. Push a <script> onto the vela-page-editor-blocks stack to hook
 *      into the admin Page Builder (only needed if you add a block type)
 *
 * None of the above requires modifying core. The plugin is a drop-in.
 */
class SnippetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing needed here for this plugin. If you have bindings,
        // do them here (e.g. $this->app->singleton(Foo::class, ...)).
    }

    public function boot(): void
    {
        // ─── 1. Load our own resources ────────────────────────────────

        // Migrations: run via `php artisan migrate`. The one file in
        // database/migrations/ will create vela_snippets.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Views namespace: our views are referenced as
        // `vela-snippets::admin.index`, `vela-snippets::public.block`, etc.
        // Keep the namespace unique — don't collide with another plugin.
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vela-snippets');

        // Admin routes: wrap in the same middleware stack that core uses
        // for /admin/* (web + vela.auth + vela.2fa + vela.gates). This
        // ensures the Vela guard resolves, 2FA is enforced, and gates
        // work. The config key lets a host app override the stack if
        // they need to (e.g. add rate-limiting).
        Route::group([
            'middleware' => config('vela.middleware.admin', ['web', 'vela.auth', 'vela.2fa', 'vela.gates']),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        });


        // ─── 2. Hook into Vela's registries ───────────────────────────
        //
        // Defer to `booted()` so core's service provider has finished
        // booting (and the Vela singleton is fully wired) before we try
        // to resolve it. If core isn't present (someone install this
        // plugin without core, somehow), we fail silently — our routes
        // and migrations just stay dormant.
        $this->app->booted(function () {
            try {
                $vela = $this->app->make(\VelaBuild\Core\Vela::class);
            } catch (\Throwable $e) {
                return;
            }

            // Admin sidebar entry. `group` values core uses: 'general',
            // 'content', 'admin'. `gate` is a permission title that
            // controls visibility (non-matching users don't see the
            // menu item or get redirected from its route).
            $vela->registerMenuItem('snippets', [
                'label' => 'Snippets',
                'icon'  => 'fas fa-code',
                'group' => 'content',
                'order' => 50,
                'route' => 'vela.admin.snippets.index',
                'gate'  => 'snippets_access',
            ]);

            // Page Builder block type. The registry holds the server-side
            // config; there's also a client-side JS registration below
            // (step 4) that tells the admin JS how to render the block
            // picker entry and editor UI.
            //
            //   'view'    = public render template
            //   'editor'  = admin-side form template (rendered inside
            //               the page editor modal)
            //   'defaults' = initial content/settings when a user adds a
            //                fresh instance of this block
            $vela->registerBlock('snippet', [
                'label'  => 'Snippet',
                'icon'   => 'fas fa-code',
                'group'  => 'content',
                'view'   => 'vela-snippets::public.block',
                'editor' => 'vela-snippets::admin.block-form',
                'defaults' => [
                    'content'  => ['snippet_id' => null],
                    'settings' => [],
                ],
            ]);
        });


        // ─── 3. Permissions ───────────────────────────────────────────
        $this->bootPermissions();


        // ─── 4. Page Builder JS registration ──────────────────────────
        //
        // Why this isn't just `<script>PageEditor.registerBlockType(...)</script>`:
        //
        // The admin Page Builder's block picker is driven by the JS
        // object `PageEditor.blockTypes`, populated by the core script
        // `vendor/vela/js/page-editor.js` via `PageEditor.registerBlockType(name, cfg)`.
        // For our block type to appear, we must call that function too.
        //
        // Core's admin layout provides an ordered extension point:
        //   @stack('scripts')                 ← page-editor.js loads here
        //   @stack('vela-page-editor-blocks') ← our <script> emits AFTER
        //
        // By the time our stack emits, `PageEditor` is defined. So a
        // plain `registerBlockType(...)` call works — no DOMContentLoaded,
        // no polling.
        //
        // We register a view composer on core's block-editor partial so
        // our push happens inside the same render tree — which keeps the
        // push state alive until the outer admin layout's @stack fires.
        //
        // For plugins that don't add a Page Builder block, you can skip
        // this step entirely.
        View::composer(
            'vela::admin.pages.partials.block-editor',
            function () {
                try {
                    $snippets = Models\Snippet::where('is_active', true)
                        ->orderBy('category')->orderBy('name')
                        ->get(['id', 'name', 'slug', 'category', 'description'])
                        ->toArray();
                } catch (\Throwable $e) {
                    // No DB / table missing — ship an empty list so the
                    // picker still shows "Snippet" with a helpful empty
                    // state instead of 500-ing.
                    $snippets = [];
                }
                // Render our partial. The partial itself is wrapped in
                // @push('vela-page-editor-blocks') ... @endpush, so just
                // calling render() is enough — its contents get buffered
                // onto the stack and the admin layout emits them later.
                view(
                    'vela-snippets::admin.page-editor-block',
                    ['__snippets' => $snippets]
                )->render();
            }
        );
    }

    /**
     * Seed permission rows into vela_permissions so admins can assign
     * them to roles via the roles UI. Core's VelaAuthGates middleware
     * defines the Laravel gates dynamically from the permission→role
     * mapping on every admin request — so plugins don't need their own
     * `Gate::define()` calls.
     *
     * Guarded with `Schema::hasTable()` + `try/catch` so this is safe on
     * no-DB static-cache deploys where the migration hasn't run and on
     * fresh installs where the vela_permissions table doesn't exist yet.
     *
     * Run from `booted()` so core's migrations have a chance to run
     * first if they're part of the same artisan invocation.
     */
    protected function bootPermissions(): void
    {
        $this->app->booted(function () {
            try {
                if (!Schema::hasTable('vela_permissions')) return;
                foreach ([
                    'snippets_access' => 'View snippets',
                    'snippets_create' => 'Create snippets',
                    'snippets_edit'   => 'Edit snippets',
                    'snippets_delete' => 'Delete snippets',
                ] as $title => $description) {
                    \VelaBuild\Core\Models\Permission::firstOrCreate(
                        ['title' => $title],
                        ['description' => $description]
                    );
                }
            } catch (\Throwable $e) {
                // DB unavailable — don't block the boot.
            }
        });
    }
}
