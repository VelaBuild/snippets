@php
    // Admin editor for a Snippet block inside the Page Builder.
    // Renders a <select> with every active snippet, grouped by category.
    $selectedId = $block->content['snippet_id'] ?? null;
    $snippets = \VelaBuild\Snippets\Models\Snippet::where('is_active', true)
        ->orderBy('category')
        ->orderBy('name')
        ->get()
        ->groupBy('category');
@endphp
<div class="form-group">
    <label for="block-snippet-id">Snippet</label>
    <select id="block-snippet-id" name="content[snippet_id]" class="form-control">
        <option value="">— choose a snippet —</option>
@foreach($snippets as $category => $group)
        <optgroup label="{{ $category }}">
@foreach($group as $s)
            <option value="{{ $s->id }}" @selected((int) $selectedId === (int) $s->id)>{{ $s->name }}</option>
@endforeach
        </optgroup>
@endforeach
    </select>
@if($snippets->isEmpty())
    <small class="form-text text-muted">
        No snippets yet. <a href="{{ route('vela.admin.snippets.create') }}" target="_blank">Create one →</a>
    </small>
@else
    <small class="form-text text-muted">
        <a href="{{ route('vela.admin.snippets.index') }}" target="_blank">Manage snippets →</a>
@if($selectedId)
        · <a href="{{ route('vela.admin.snippets.edit', $selectedId) }}" target="_blank">Edit this one</a>
        · <a href="{{ route('vela.admin.snippets.preview', $selectedId) }}" target="_blank">Preview</a>
@endif
    </small>
@endif
</div>
