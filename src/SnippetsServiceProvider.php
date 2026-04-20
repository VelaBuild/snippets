<?php

namespace VelaBuild\Snippets;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Vela Snippets plugin — reference implementation.
 *
 * This file shows the three things every Vela plugin should do:
 *
 *   1. Load its own migrations, views, routes, and translations.
 *   2. Register an admin menu entry + permissions.
 *   3. If it extends the Page Builder, register a block type via the
 *      BlockRegistry on the Vela facade/singleton.
 *
 * No changes to core are required for this plugin to function; everything
 * hooks in via `app(Vela::class)`.
 */
class SnippetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Load resources ------------------------------------------------
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vela-snippets');

        // Routes go through the same middleware stack as core admin routes so
        // the Vela auth guard, 2FA, and gates all apply. `vela.locale` keeps
        // the UI in the admin's chosen language.
        Route::group([
            'middleware' => config('vela.middleware.admin', ['web', 'vela.auth', 'vela.2fa', 'vela.gates']),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        });

        // 2. Register with Vela's registries -------------------------------
        $this->app->booted(function () {
            try {
                $vela = $this->app->make(\VelaBuild\Core\Vela::class);
            } catch (\Throwable $e) {
                return; // Core not available — nothing to register against.
            }

            // Admin menu entry under "Content".
            $vela->registerMenuItem('snippets', [
                'label' => 'Snippets',
                'icon'  => 'fas fa-code',
                'group' => 'content',
                'order' => 50,
                'route' => 'vela.admin.snippets.index',
                'gate'  => 'snippets_access',
            ]);

            // Page Builder block type — lets page authors drop a snippet
            // into any row. `view` = public render; `editor` = admin form.
            $vela->registerBlock('snippet', [
                'label' => 'Snippet',
                'icon'  => 'fas fa-code',
                'group' => 'content',
                'view'  => 'vela-snippets::public.block',
                'editor' => 'vela-snippets::admin.block-form',
                'defaults' => [
                    'content'  => ['snippet_id' => null],
                    'settings' => [],
                ],
            ]);
        });

        // 3. Permissions ---------------------------------------------------
        $this->bootPermissions();

        // 4. Page Builder JS registration ---------------------------------
        // When the page-editor partial renders, render our registration
        // script — it @pushes itself onto core's `vela-page-editor-blocks`
        // stack, so core's admin layout emits the <script> inside the page
        // where PageEditor.registerBlockType is callable.
        //
        // This is the pattern every plugin that adds a Page Builder block
        // type should follow.
        \Illuminate\Support\Facades\View::composer(
            'vela::admin.pages.partials.block-editor',
            function () {
                try {
                    $snippets = Models\Snippet::where('is_active', true)
                        ->orderBy('category')->orderBy('name')
                        ->get(['id', 'name', 'slug', 'category', 'description'])
                        ->toArray();
                } catch (\Throwable $e) {
                    $snippets = [];
                }
                // Render once; the view body is wrapped in @push/@endpush.
                view('vela-snippets::admin.page-editor-block', ['__snippets' => $snippets])->render();
            }
        );
    }

    /**
     * Seed permission rows into vela_permissions so admins can assign them
     * to roles. Gates are wired up dynamically by Core's VelaAuthGates
     * middleware based on the permission→role mapping, so the plugin
     * doesn't need to call Gate::define() itself.
     *
     * Guarded with Schema::hasTable() so this is safe on no-DB static-cache
     * deploys where the migration hasn't run.
     */
    protected function bootPermissions(): void
    {
        $this->app->booted(function () {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('vela_permissions')) return;
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
                // DB unavailable — skip silently.
            }
        });
    }
}
