<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveSlugged;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function routeBase(): string
    {
        return 'admin.categories';
    }

    protected function moduleLabel(): string
    {
        return 'Categories';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $filtered = $request->hasAny(['q']);

        return view('admin.categories.index', [
            'records' => Category::query()
                ->withCount('posts')
                ->search($request->string('q')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => ! $filtered,
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.form', ['record' => new Category]);
    }

    public function store(SaveCategoryRequest $request, SaveSlugged $save): RedirectResponse
    {
        $save(new Category(['sort_order' => Category::nextSortOrder()]), $request->validated());

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', ['record' => $category]);
    }

    public function update(SaveCategoryRequest $request, Category $category, SaveSlugged $save): RedirectResponse
    {
        $save($category, $request->validated());

        return redirect()->route('admin.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category moved to Trash.');
    }
}
