<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyBulkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkActionRequest;
use App\Http\Requests\Admin\SaveTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tags have no Trash, order or publish state (deleting one only detaches it), so this is a plain module like Redirects. */
class TagController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tag::class);
        $term = $request->string('q')->toString();

        return view('admin.tags.index', [
            'records' => Tag::query()
                ->withCount('posts')
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tag::class);

        return view('admin.tags.form', ['record' => new Tag]);
    }

    public function store(SaveTagRequest $request): RedirectResponse
    {
        Tag::create(array_filter($request->validated(), fn ($value) => filled($value)));

        return redirect()->route('admin.tags.index')->with('status', 'Tag created.');
    }

    public function edit(Tag $tag): View
    {
        $this->authorize('update', $tag);

        return view('admin.tags.form', ['record' => $tag]);
    }

    public function update(SaveTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update(array_filter($request->validated(), fn ($value) => filled($value)));

        return redirect()->route('admin.tags.index')->with('status', 'Tag saved.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('status', 'Tag deleted. It was removed from its articles.');
    }

    public function bulk(BulkActionRequest $request, ApplyBulkAction $apply): RedirectResponse
    {
        $this->authorize('delete', Tag::class);
        $count = $apply(Tag::class, 'delete', $request->validated('ids'));

        return back()->with('status', "{$count} tag(s) deleted.");
    }
}
