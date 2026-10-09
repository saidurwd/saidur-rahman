<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Experience::class;
    }

    protected function routeBase(): string
    {
        return 'admin.experiences';
    }

    protected function moduleLabel(): string
    {
        return 'Experiences';
    }

    protected function labelAttribute(): string
    {
        return 'company';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Experience::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.experiences.index', [
            'records' => Experience::query()
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => false,
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Experience::class);

        return view('admin.experiences.form', ['record' => new Experience(['is_published' => true])]);
    }

    public function store(SaveExperienceRequest $request): RedirectResponse
    {
        Experience::create($request->validated());

        return redirect()->route('admin.experiences.index')->with('status', 'Experience created.');
    }

    public function edit(Experience $experience): View
    {
        $this->authorize('update', $experience);

        return view('admin.experiences.form', ['record' => $experience]);
    }

    public function update(SaveExperienceRequest $request, Experience $experience): RedirectResponse
    {
        $experience->update($request->validated());

        return redirect()->route('admin.experiences.index')->with('status', 'Experience saved.');
    }

    public function destroy(Experience $experience): RedirectResponse
    {
        $this->authorize('delete', $experience);
        $experience->delete();

        return redirect()->route('admin.experiences.index')->with('status', 'Experience moved to Trash.');
    }
}
