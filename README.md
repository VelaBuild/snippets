# velabuild/snippets

**The first official Vela plugin — also the reference implementation for plugin authors.**

Adds a **Snippets** page to Vela admin where users, developers, and AI can create reusable chunks of HTML/CSS/JS. Every snippet shows up in the Page Builder as a `Snippet` block: pick one from the dropdown and it's on the page.

```
Admin → Content → Snippets
Page Builder → Add block → Snippet → (pick from dropdown)
```

---

## What it demonstrates (for plugin authors)

Every Vela plugin should do three things. This package shows each in one file.

### 1. Load your own resources

```php
// src/SnippetsServiceProvider.php
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vela-snippets');
    $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
}
```

### 2. Register with Vela's registries

```php
$this->app->booted(function () {
    $vela = $this->app->make(\VelaBuild\Core\Vela::class);

    // Admin menu entry
    $vela->registerMenuItem('snippets', [
        'label' => 'Snippets',
        'icon'  => 'fas fa-code',
        'group' => 'content',
        'route' => 'vela.admin.snippets.index',
        'gate'  => 'snippets_access',
    ]);

    // Page Builder block type
    $vela->registerBlock('snippet', [
        'label'  => 'Snippet',
        'view'   => 'vela-snippets::public.block',    // public render
        'editor' => 'vela-snippets::admin.block-form', // Page-Builder editor
        'defaults' => ['content' => ['snippet_id' => null]],
    ]);
});
```

### 3. Permissions

```php
foreach (['snippets_access', 'snippets_create', 'snippets_edit', 'snippets_delete'] as $p) {
    Gate::define($p, fn($user) => $user?->hasPermission($p));
}
// Seed into vela_permissions so they show up in the roles UI.
```

That's the whole plugin lifecycle. No core changes required.

---

## What the plugin adds

- **`vela_snippets` table** — `name`, `slug`, `category`, `html`, `css`, `js`, `scope_css`, `is_active`
- **`/admin/snippets`** — list, create, edit, preview, delete
- **Live preview iframe** during editing (scoped so snippet styles can't touch the admin chrome)
- **Scoped CSS by default** — every selector auto-prefixed with `[data-snippet="{id}"]` so a snippet's styles can't bleed into the host page. Opt out per snippet.
- **IIFE-wrapped JS** — `(function(){ … })()` so local vars don't leak into `window`.
- **Page Builder integration** — add a `Snippet` block to any row, pick from a dropdown of active snippets
- **Four permissions** — `snippets_access` / `_create` / `_edit` / `_delete`

## Conventions followed

- Namespace: `VelaBuild\Snippets\`
- DB table: `vela_snippets` (prefix matches every other Vela table)
- Model uses `SoftDeletes`, `$table` explicit, `serializeDate()` → `Y-m-d H:i:s`
- Auth guard: `vela`
- Routes named `vela.admin.snippets.*`

## Install

```bash
composer require velabuild/snippets
php artisan migrate
```

Assign the `snippets_*` permissions to the roles that should get access, then visit `/admin/snippets`.

Example snippets ship in `database/seeders/SnippetsExampleSeeder.php` — run it to get three live demos (callout, copy-to-clipboard, countdown).

## Directory layout

```
src/
  SnippetsServiceProvider.php       # entry point (reads as a tutorial)
  Models/Snippet.php                # with render() method
  Services/CssScoper.php            # auto-scope snippet CSS selectors
  Http/Controllers/Admin/SnippetController.php
resources/views/
  admin/index.blade.php             # list
  admin/form.blade.php              # create/edit with live preview iframe
  admin/preview.blade.php           # iframe source for preview
  admin/block-form.blade.php        # Page Builder editor (dropdown)
  public/block.blade.php            # Page Builder public render
routes/admin.php
database/migrations/2026_04_20_000001_create_vela_snippets_table.php
database/seeders/SnippetsExampleSeeder.php
```

## License

MIT © Vela Build
