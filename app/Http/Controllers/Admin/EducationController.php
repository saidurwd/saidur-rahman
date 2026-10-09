<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EducationController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Education::class;
    }

    protected function routeBase(): string
    {
        return 'admin.educations';
    }

    protected function moduleLabel(): string
    {
        return 'Education';
    }

    protected function labelAttribute(): string
    {
        return 'institution';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Education::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.educations.index', [
            'records' => Education::query()
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
        $this->authorize('create', Education::class);

        return view('admin.educations.form', ['record' => new Education(['is_published' => true])]);
    }

    public function store(SaveEducationRequest $request): RedirectResponse
    {
        Education::create($request->validated() + ['sort_order' => Education::nextSortOrder()]);

        return redirect()->route('admin.educations.index')->with('status', 'Education entry created.');
    }

    public function edit(Education $education): View
    {
        $this->authorize('update', $education);

        return view('admin.educations.form', ['record' => $education]);
    }

    public function update(SaveEducationRequest $request, Education $education): RedirectResponse
    {
        $education->update($request->validated());

        return redirect()->route('admin.educations.index')->with('status', 'Education entry saved.');
    }

    public function destroy(Education $education): RedirectResponse
    {
        $this->authorize('delete', $education);
        $education->delete();

        return redirect()->route('admin.educations.index')->with('status', 'Education entry moved to Trash.');
    }
}
