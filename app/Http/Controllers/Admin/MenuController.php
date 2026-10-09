<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Menu::class;
    }

    protected function routeBase(): string
    {
        return 'admin.menus';
    }

    protected function moduleLabel(): string
    {
        return 'Menus';
    }

    protected function labelAttribute(): string
    {
        return 'label';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Menu::class);

        $filtered = $request->hasAny(['q', 'state', 'location']);

        return view('admin.menus.index', [
            'records' => Menu::query()
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->when($request->filled('location'), fn ($q) => $q->where('location', $request->input('location')))
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => ! $filtered,
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Menu::class);

        return view('admin.menus.form', ['record' => new Menu(['is_published' => true])]);
    }

    public function store(SaveMenuRequest $request): RedirectResponse
    {
        Menu::create($request->validated() + ['sort_order' => Menu::nextSortOrder()]);

        return redirect()->route('admin.menus.index')->with('status', 'Menu item created.');
    }

    public function edit(Menu $menu): View
    {
        $this->authorize('update', $menu);

        return view('admin.menus.form', ['record' => $menu]);
    }

    public function update(SaveMenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());

        return redirect()->route('admin.menus.index')->with('status', 'Menu item saved.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('status', 'Menu item moved to Trash.');
    }
}
