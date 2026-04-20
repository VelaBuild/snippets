@extends('vela::layouts.admin')

@section('breadcrumb', 'Snippets')

@section('content')
<div class="vela-page-head">
    <div>
        <h1 class="vela-title">Snippets</h1>
        <p class="vela-subtitle">Reusable HTML, CSS, and JS you can drop into any page.</p>
    </div>
    @can('snippets_create')
    <a href="{{ route('vela.admin.snippets.create') }}" class="btn btn-success">
        <i class="fas fa-plus"></i> New snippet
    </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('vela.admin.snippets.index') }}" class="snippets-filter">
            <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Search snippets…" class="form-control">
            @if($categories->count() > 1)
                <select name="category" class="form-control" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" @selected(($cat ?? null) === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>

        @if($snippets->count() === 0)
            <div class="text-center" style="padding: 48px 24px; color: var(--v-fg-muted);">
                <div style="font-size: 48px; margin-bottom: 12px;">✂</div>
                <h3 style="margin: 0 0 6px; color: var(--v-fg);">No snippets yet</h3>
                <p style="margin: 0 0 20px;">Snippets let you reuse a chunk of HTML / CSS / JS across any page.</p>
                @can('snippets_create')
                    <a href="{{ route('vela.admin.snippets.create') }}" class="btn btn-success">Create your first snippet</a>
                @endcan
            </div>
        @else
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="width: 1%;">Uses</th>
                        <th style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($snippets as $s)
                    <tr>
                        <td><strong>{{ $s->name }}</strong><br><code style="font-size: 11px; color: var(--v-fg-subtle);">{{ $s->slug }}</code></td>
                        <td><span class="badge badge-secondary">{{ $s->category }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($s->description, 80) }}</td>
                        <td>
                            @if($s->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Draft</span>
                            @endif
                        </td>
                        <td>{{ $s->uses_count }}</td>
                        <td>
                            <a href="{{ route('vela.admin.snippets.preview', $s) }}" target="_blank" class="btn btn-sm btn-secondary">Preview</a>
                            @can('snippets_edit')
                                <a href="{{ route('vela.admin.snippets.edit', $s) }}" class="btn btn-sm btn-primary">Edit</a>
                            @endcan
                            @can('snippets_delete')
                                <form method="POST" action="{{ route('vela.admin.snippets.destroy', $s) }}" style="display:inline-block;" onsubmit="return confirm('Delete this snippet?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $snippets->links() }}</div>
        @endif
    </div>
</div>

<style>
    .snippets-filter { display: flex; gap: 8px; margin-bottom: 20px; }
    .snippets-filter input[type="search"] { max-width: 360px; }
    .snippets-filter select { max-width: 220px; }
    .vela-page-head {
        display: flex; justify-content: space-between; align-items: start;
        gap: 16px; flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .vela-title { font-family: var(--v-font-display, inherit); font-size: 28px; margin: 0 0 4px; font-weight: 500; letter-spacing: -0.01em; }
    .vela-subtitle { color: var(--v-fg-muted); margin: 0; font-size: 14px; }
</style>
@endsection
