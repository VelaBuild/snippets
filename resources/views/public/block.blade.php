@php
    // Render a Snippet block inside the Page Builder. The block stores a
    // snippet_id on its content payload; we load the snippet, render it
    // (scoped CSS + IIFE JS + HTML), and bump the uses counter.
    $id = $block->content['snippet_id'] ?? null;
    $snippet = null;
    if ($id) {
        try {
            $snippet = \VelaBuild\Snippets\Models\Snippet::find($id);
        } catch (\Throwable $e) {
            $snippet = null;
        }
    }
@endphp
@if($snippet && $snippet->is_active)
    {!! $snippet->render() !!}
@elseif(config('app.debug'))
    <div class="vela-snippet-missing" style="padding:10px; border:1px dashed #D94A42; color:#D94A42; font-family:ui-monospace, monospace; font-size:12px;">
        Snippet block — snippet #{{ $id ?? '?' }} not found or inactive.
    </div>
@endif
