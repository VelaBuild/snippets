<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $snippet->name ?: 'Snippet preview' }}</title>
    <style>
        /* Minimal reset + page chrome for the preview iframe. Intentionally
           plain so the snippet's own styling is the only visual identity. */
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1a2740; background: #fbf9f5;
            padding: 24px;
            line-height: 1.5;
        }
        .preview-banner {
            font-family: ui-monospace, Menlo, monospace;
            font-size: 11px; color: #6B7388;
            letter-spacing: 0.08em; text-transform: uppercase;
            margin-bottom: 16px;
            padding-bottom: 12px; border-bottom: 1px dashed #DCE0E9;
        }
    </style>
</head>
<body>
    <div class="preview-banner">Snippet preview · {{ $snippet->name ?: 'untitled' }}</div>
    {!! $snippet->render() !!}
</body>
</html>
