<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveSlugged;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Page::class;
    }

    protected function routeBase(): string
    {
        return 'admin.pages';
    }

    protected function moduleLabel(): string
    {
        return 'Pages';
    }

    protected function labelAttribute(): string
    {
        return 'title';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.pages.index', [
            'records' => Page::query()
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => ! $filtered,
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('admin.pages.form', ['record' => new Page(['is_published' => false])]);
    }

    public function store(SavePageRequest $request, SaveSlugged $save): RedirectResponse
    {
        $save(new Page(['sort_order' => Page::nextSortOrder()]), $request->validated(), ['body']);

        return redirect()->route('admin.pages.index')->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('admin.pages.form', ['record' => $page]);
    }

    public function update(SavePageRequest $request, Page $page, SaveSlugged $save): RedirectResponse
    {
        $save($page, $request->validated(), ['body']);

        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page moved to Trash.');
    }
}
