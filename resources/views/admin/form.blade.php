@extends('vela::layouts.admin')

@section('breadcrumb', $snippet->exists ? 'Edit snippet' : 'New snippet')

@section('content')
<div class="vela-page-head">
    <div>
        <h1 class="vela-title">{{ $snippet->exists ? 'Edit snippet' : 'New snippet' }}</h1>
        <p class="vela-subtitle">HTML + CSS + JS, previewed live.</p>
    </div>
    <a href="{{ route('vela.admin.snippets.index') }}" class="btn btn-secondary">← Back to list</a>
</div>

<form method="POST" action="{{ $snippet->exists ? route('vela.admin.snippets.update', $snippet) : route('vela.admin.snippets.store') }}" id="snippet-form" class="snippet-form">
    @csrf
    @if($snippet->exists) @method('PUT') @endif

    <div class="snippet-grid">
        <div class="snippet-editors card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" class="form-control" required maxlength="150" value="{{ old('name', $snippet->name) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="category">Category</label>
                        <input id="category" name="category" type="text" class="form-control" maxlength="80" value="{{ old('category', $snippet->category ?: 'general') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input id="description" name="description" type="text" class="form-control" maxlength="500" value="{{ old('description', $snippet->description) }}">
                </div>

                <div class="editor-tabs" role="tablist">
                    <button type="button" class="editor-tab is-active" data-tab="html">HTML</button>
                    <button type="button" class="editor-tab" data-tab="css">CSS</button>
                    <button type="button" class="editor-tab" data-tab="js">JS</button>
                </div>

                <div class="editor-panes">
                    <textarea name="html" class="editor-pane is-active" data-pane="html" spellcheck="false" placeholder="&lt;div class=&quot;hello&quot;&gt;Hi there&lt;/div&gt;">{{ old('html', $snippet->html) }}</textarea>
                    <textarea name="css" class="editor-pane" data-pane="css" spellcheck="false" placeholder=".hello { color: teal; }">{{ old('css', $snippet->css) }}</textarea>
                    <textarea name="js" class="editor-pane" data-pane="js" spellcheck="false" placeholder="console.log('mounted');">{{ old('js', $snippet->js) }}</textarea>
                </div>

                <div class="form-row mt-3">
                    <div class="form-group col-md-6">
                        <label><input type="checkbox" name="scope_css" value="1" {{ old('scope_css', $snippet->scope_css ?? true) ? 'checked' : '' }}> Scope CSS to this snippet <small>(auto-prefix selectors so styles can't leak)</small></label>
                    </div>
                    <div class="form-group col-md-6">
                        <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $snippet->is_active ?? true) ? 'checked' : '' }}> Active <small>(hidden snippets don't render on pages)</small></label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">{{ $snippet->exists ? 'Save' : 'Create snippet' }}</button>
                </div>
            </div>
        </div>

        <div class="snippet-preview card">
            <div class="card-header">
                Live preview
                <button type="button" class="btn btn-sm btn-secondary float-right" id="refresh-preview">Refresh</button>
            </div>
            <div class="card-body" style="padding: 0;">
                <iframe id="preview-frame" title="Snippet preview" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</form>

<style>
    .snippet-grid { display: grid; grid-template-columns: 1.1fr 1fr; gap: 16px; }
    @media (max-width: 1100px) { .snippet-grid { grid-template-columns: 1fr; } }

    .editor-tabs { display: flex; gap: 2px; margin-top: 20px; border-bottom: 1px solid var(--v-border); }
    .editor-tab {
        padding: 8px 16px; border: none; background: transparent;
        font-family: var(--v-font-mono); font-size: 12px; font-weight: 600;
        color: var(--v-fg-muted); cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }
    .editor-tab.is-active { color: var(--v-fg-accent); border-bottom-color: var(--v-accent); }

    .editor-panes { position: relative; min-height: 300px; }
    .editor-pane {
        display: none; width: 100%;
        min-height: 360px;
        font-family: var(--v-font-mono, monospace); font-size: 13px;
        padding: 14px 16px;
        border: 1px solid var(--v-border); border-top: none;
        border-radius: 0 0 var(--v-r-sm) var(--v-r-sm);
        background: var(--v-bg-muted);
        color: var(--v-fg);
        resize: vertical;
        tab-size: 2;
        line-height: 1.5;
    }
    .editor-pane.is-active { display: block; }
    .editor-pane:focus { outline: none; box-shadow: var(--v-shadow-ring); border-color: var(--v-accent); }

    .form-actions { margin-top: 20px; display: flex; gap: 8px; }

    #preview-frame {
        width: 100%; height: 560px;
        border: 0;
        background: #fff;
        border-radius: 0 0 var(--v-r-md) var(--v-r-md);
    }
    .snippet-preview .card-header { display: flex; justify-content: space-between; align-items: center; }
</style>

<script>
(function() {
    const form = document.getElementById('snippet-form');
    const frame = document.getElementById('preview-frame');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const tabs = form.querySelectorAll('.editor-tab');
    const panes = form.querySelectorAll('.editor-pane');

    // Tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.toggle('is-active', t === tab));
            const name = tab.dataset.tab;
            panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));
        });
    });

    // Live preview via debounced POST. No page reload, no save round-trip.
    let timer = null;
    function refresh() {
        const data = new FormData();
        data.set('html', form.html.value);
        data.set('css', form.css.value);
        data.set('js', form.js.value);
        data.set('scope_css', form.scope_css.checked ? '1' : '0');

        fetch('{{ route('vela.admin.snippets.preview-live') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' },
            body: data
        })
        .then(r => r.text())
        .then(html => {
            const doc = frame.contentDocument;
            doc.open(); doc.write(html); doc.close();
        });
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 350); }

    ['html', 'css', 'js'].forEach(k => form[k].addEventListener('input', schedule));
    form.scope_css.addEventListener('change', refresh);
    document.getElementById('refresh-preview').addEventListener('click', refresh);

    refresh(); // initial paint
})();
</script>
@endsection
