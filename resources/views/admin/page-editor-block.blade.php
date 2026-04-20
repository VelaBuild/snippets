{{-- Register the `snippet` block type on the client-side PageEditor.
     The vela-page-editor-blocks stack emits AFTER core's page-editor.js,
     so PageEditor is already defined by the time this script runs. --}}
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
