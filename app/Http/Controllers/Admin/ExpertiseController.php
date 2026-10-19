<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveSlugged;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveExpertiseRequest;
use App\Models\Expertise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpertiseController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Expertise::class;
    }

    protected function routeBase(): string
    {
        return 'admin.expertise';
    }

    protected function moduleLabel(): string
    {
        return 'Expertise';
    }

    protected function labelAttribute(): string
    {
        return 'title';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expertise::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.expertise.index', [
            'records' => Expertise::query()
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
        $this->authorize('create', Expertise::class);

        return view('admin.expertise.form', ['record' => new Expertise(['is_published' => false])]);
    }

    public function store(SaveExpertiseRequest $request, SaveSlugged $save): RedirectResponse
    {
        $save(new Expertise(['sort_order' => Expertise::nextSortOrder()]), $request->validated(), ['body'], ['summary']);

        return redirect()->route('admin.expertise.index')->with('status', 'Expertise created.');
    }

    public function edit(Expertise $expertise): View
    {
        $this->authorize('update', $expertise);

        return view('admin.expertise.form', ['record' => $expertise]);
    }

    public function update(SaveExpertiseRequest $request, Expertise $expertise, SaveSlugged $save): RedirectResponse
    {
        $save($expertise, $request->validated(), ['body'], ['summary']);

        return redirect()->route('admin.expertise.index')->with('status', 'Expertise saved.');
    }

    public function destroy(Expertise $expertise): RedirectResponse
    {
        $this->authorize('delete', $expertise);
        $expertise->delete();

        return redirect()->route('admin.expertise.index')->with('status', 'Expertise moved to Trash.');
    }
}
