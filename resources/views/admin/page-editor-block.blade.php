{{-- Register the `snippet` block type on the client-side PageEditor.
     Pushed onto the vela-page-editor-blocks stack (declared in core's
     admin layout) whenever the page-editor partial renders. --}}
@push('vela-page-editor-blocks')
<script>
(function() {
    // The vela-page-editor-blocks stack emits ABOVE the scripts stack where
    // page-editor.js loads, so PageEditor isn't defined yet when this runs.
    // Defer registration until DOM is ready (all synchronous scripts have
    // executed by then, including page-editor.js).
    var SNIPPETS = @json($__snippets ?? []);

    function register() {
        if (typeof PageEditor === 'undefined' || typeof PageEditor.registerBlockType !== 'function') return false;
        PageEditor.registerBlockType('snippet', blockConfig());
        return true;
    }

    function blockConfig() {
        return {
        icon: 'fa-code',
        label: 'Snippet',
        defaults: { content: { snippet_id: null }, settings: {} },

        renderPreview: function(block) {
            var id = block.content && block.content.snippet_id ? parseInt(block.content.snippet_id, 10) : 0;
            var snip = SNIPPETS.find(function(s) { return s.id === id; });
            if (!snip) return '<em class="text-muted">No snippet chosen</em>';
            var desc = snip.description ? ' — ' + snip.description : '';
            return '<div style="padding:10px 14px; border:1px solid #e9ecef; border-radius:6px; background:#fafafa;">'
                 + '<strong>✂ ' + escHtml(snip.name) + '</strong>'
                 + '<span style="color:#6B7388; font-size:12px;">' + escHtml(desc) + '</span>'
                 + '</div>';
        },

        renderEditor: function(block) {
            var selectedId = block.content && block.content.snippet_id ? parseInt(block.content.snippet_id, 10) : 0;

            if (!SNIPPETS.length) {
                return '<div class="alert alert-warning">'
                     + 'No snippets yet. <a href="' + window.location.origin + window.location.pathname.replace(/\\/admin\\/pages.*/, '/admin/snippets/create') + '" target="_blank">Create one</a> and come back.'
                     + '</div>';
            }

            // Group by category
            var byCat = {};
            SNIPPETS.forEach(function(s) {
                var c = s.category || 'general';
                if (!byCat[c]) byCat[c] = [];
                byCat[c].push(s);
            });

            var html = '<div class="form-group">'
                     + '<label for="snippet-id-select">Choose a snippet</label>'
                     + '<select id="snippet-id-select" class="form-control">'
                     + '<option value="">— choose a snippet —</option>';
            Object.keys(byCat).sort().forEach(function(cat) {
                html += '<optgroup label="' + escHtml(cat) + '">';
                byCat[cat].forEach(function(s) {
                    var sel = s.id === selectedId ? ' selected' : '';
                    html += '<option value="' + s.id + '"' + sel + '>' + escHtml(s.name) + '</option>';
                });
                html += '</optgroup>';
            });
            html += '</select>';
            html += '<small class="form-text text-muted">'
                  + '<a href="/admin/snippets" target="_blank">Manage snippets ↗</a>'
                  + '</small></div>';
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
        };
    }

    // Try now (in case PageEditor already loaded), else wait for DOM ready.
    if (!register()) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', register);
        } else {
            register();
        }
    }

    function escHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
</script>
@endpush
