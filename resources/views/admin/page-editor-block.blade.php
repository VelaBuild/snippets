{{--
    Client-side Page Builder block registration — reference pattern.

    Core's admin layout has two <script> extension points:

        @stack('scripts')                    ← page-editor.js emits here
        @stack('vela-page-editor-blocks')    ← plugins emit here (AFTER)

    Because our push lands AFTER page-editor.js has executed, we can call
    PageEditor.registerBlockType(...) directly — no readiness check, no
    polling, no race conditions.

    This partial is pushed onto the stack by a view composer registered
    in SnippetsServiceProvider. The composer fires whenever core's
    page-editor partial is rendered, so this <script> only ships on
    admin page-edit screens — not on every admin page.

    The `$__snippets` view data is the current list of active snippets
    (id / name / slug / category / description). We JSON-encode it into
    the client-side bundle so the block picker + editor can populate its
    dropdown without making an extra AJAX call.

    PageEditor.registerBlockType(name, config) expects:
      icon            FontAwesome class suffix (e.g. 'fa-code')
      label           Button label in the block picker
      defaults        Initial { content, settings } for a new instance
      renderPreview   (block) => string — summary shown in the page editor
      renderEditor    (block) => string — full form HTML for the modal
      initEditor      (block) => void  — wire up event listeners
      collectData     (block) => { content, settings } — reads the form back
--}}
@push('vela-page-editor-blocks')
<script>
(function() {
    var SNIPPETS = @json($__snippets ?? []);

    PageEditor.registerBlockType('snippet', {
        icon: 'fa-code',
        label: 'Snippet',
        defaults: { content: { snippet_id: null }, settings: {} },

        renderPreview: function(block) {
            var id = block.content && block.content.snippet_id ? parseInt(block.content.snippet_id, 10) : 0;
            var snip = SNIPPETS.find(function(s) { return s.id === id; });
            if (!snip) return '<em class="text-muted">No snippet chosen</em>';
            var desc = snip.description ? ' — ' + snip.description : '';
            return '<div style="padding:10px 14px; border:1px solid #e9ecef; border-radius:6px; background:#fafafa;">'
                 + '<strong>✂ ' + esc(snip.name) + '</strong>'
                 + '<span style="color:#6B7388; font-size:12px;">' + esc(desc) + '</span>'
                 + '</div>';
        },

        renderEditor: function(block) {
            var selectedId = block.content && block.content.snippet_id ? parseInt(block.content.snippet_id, 10) : 0;

            if (!SNIPPETS.length) {
                return '<div class="alert alert-warning">No snippets yet. '
                     + '<a href="/admin/snippets/create" target="_blank">Create one</a> and come back.</div>';
            }

            var byCat = {};
            SNIPPETS.forEach(function(s) {
                var c = s.category || 'general';
                (byCat[c] = byCat[c] || []).push(s);
            });

            var html = '<div class="form-group">'
                     + '<label for="snippet-id-select">Choose a snippet</label>'
                     + '<select id="snippet-id-select" class="form-control">'
                     + '<option value="">— choose a snippet —</option>';
            Object.keys(byCat).sort().forEach(function(cat) {
                html += '<optgroup label="' + esc(cat) + '">';
                byCat[cat].forEach(function(s) {
                    html += '<option value="' + s.id + '"' + (s.id === selectedId ? ' selected' : '') + '>' + esc(s.name) + '</option>';
                });
                html += '</optgroup>';
            });
            html += '</select>';
            html += '<small class="form-text text-muted"><a href="/admin/snippets" target="_blank">Manage snippets ↗</a></small>';
            html += '</div>';
            return html;
        },

        initEditor: function(block) {},

        collectData: function(block) {
            var v = document.getElementById('snippet-id-select').value;
            return {
                content: { snippet_id: v ? parseInt(v, 10) : null },
                settings: block.settings
            };
        }
    });

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
</script>
@endpush
