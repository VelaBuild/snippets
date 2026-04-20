<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $snippet->name ?: 'Snippet preview' }}</title>
    <style>
        /* Minimal iframe chrome for the edit-form live preview pane. Not a
           user-facing page — served by the previewLive endpoint into the
           <iframe> on /admin/snippets/{id}/edit. Kept intentionally plain
           so the snippet's own styles are the only visual identity. */
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1a2740; background: #fbf9f5;
            padding: 24px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    {!! $snippet->render() !!}
</body>
</html>
