<?php

namespace VelaBuild\Snippets\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use VelaBuild\Core\Http\Controllers\Controller;
use VelaBuild\Snippets\Models\Snippet;
use VelaBuild\Snippets\Services\CssScoper;

class SnippetController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('snippets_access'), 403);

        $query = Snippet::query()->orderBy('updated_at', 'desc');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($cat = $request->query('category')) {
            $query->where('category', $cat);
        }

        $snippets = $query->paginate(20)->withQueryString();
        $categories = Snippet::query()->distinct()->orderBy('category')->pluck('category');

        return view('vela-snippets::admin.index', compact('snippets', 'categories', 'q', 'cat'));
    }

    public function create()
    {
        abort_if(Gate::denies('snippets_create'), 403);
        $snippet = new Snippet(['scope_css' => true, 'is_active' => true]);
        return view('vela-snippets::admin.form', compact('snippet'));
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('snippets_create'), 403);
        $data = $this->validated($request);
        $data['created_by'] = auth('vela')->id();
        $snippet = Snippet::create($data);
        return redirect()->route('vela.admin.snippets.edit', $snippet)
            ->with('message', 'Snippet created.');
    }

    public function edit(Snippet $snippet)
    {
        abort_if(Gate::denies('snippets_edit'), 403);
        return view('vela-snippets::admin.form', compact('snippet'));
    }

    public function update(Request $request, Snippet $snippet)
    {
        abort_if(Gate::denies('snippets_edit'), 403);
        $snippet->update($this->validated($request, $snippet->id));
        return redirect()->route('vela.admin.snippets.edit', $snippet)
            ->with('message', 'Saved.');
    }

    public function destroy(Snippet $snippet)
    {
        abort_if(Gate::denies('snippets_delete'), 403);
        $snippet->delete();
        return redirect()->route('vela.admin.snippets.index')
            ->with('message', 'Deleted.');
    }

    /**
     * Iframe-safe preview. Emits only the snippet's HTML/CSS/JS plus a
     * minimal reset so the author sees the snippet alone without admin
     * chrome bleeding in.
     */
    public function preview(Snippet $snippet)
    {
        abort_if(Gate::denies('snippets_access'), 403);
        return response()
            ->view('vela-snippets::admin.preview', compact('snippet'))
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Content-Security-Policy', "frame-ancestors 'self'");
    }

    /**
     * Live preview of unsaved content. POST body = {html, css, js, scope_css}.
     * Used by the edit form's preview pane so devs see changes without saving.
     */
    public function previewLive(Request $request)
    {
        abort_if(Gate::denies('snippets_access'), 403);
        $data = $request->validate([
            'html' => 'nullable|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
            'scope_css' => 'sometimes|boolean',
        ]);
        $snippet = new Snippet(array_merge([
            'id' => 0,
            'is_active' => true,
            'scope_css' => (bool) ($data['scope_css'] ?? true),
            'html' => $data['html'] ?? '',
            'css' => $data['css'] ?? '',
            'js' => $data['js'] ?? '',
        ]));
        return response()
            ->view('vela-snippets::admin.preview', compact('snippet'))
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * JSON picker endpoint used by the Page Builder's Snippet block form.
     */
    public function pickerList(Request $request)
    {
        abort_if(Gate::denies('snippets_access'), 403);
        $q = trim((string) $request->query('q', ''));
        $query = Snippet::where('is_active', true)->orderBy('name');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }
        return response()->json([
            'snippets' => $query->limit(50)->get()->map->toPickerArray(),
        ]);
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'slug'        => 'nullable|string|max:160',
            'category'    => 'nullable|string|max:80',
            'description' => 'nullable|string|max:500',
            'html'        => 'nullable|string',
            'css'         => 'nullable|string',
            'js'          => 'nullable|string',
            'scope_css'   => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data['scope_css'] = $request->boolean('scope_css', true);
        $data['is_active'] = $request->boolean('is_active', true);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $base = $data['slug'];
        $n = 2;
        while (Snippet::where('slug', $data['slug'])->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $data['slug'] = $base . '-' . $n++;
        }

        $data['category'] = $data['category'] ?: 'general';
        return $data;
    }
}
