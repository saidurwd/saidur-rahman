<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveProject;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Project::class;
    }

    protected function routeBase(): string
    {
        return 'admin.projects';
    }

    protected function moduleLabel(): string
    {
        return 'Projects';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $filtered = $request->filled('q') || $request->filled('status');

        return view('admin.projects.index', [
            'projects' => Project::query()
                ->search($request->string('q')->toString())
                ->withStatus($request->string('status')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'statuses' => ProjectStatus::cases(),
            'reorderable' => ! $filtered,
            'trashedCount' => Project::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('admin.projects.form', ['project' => new Project(['status' => ProjectStatus::Draft])]);
    }

    public function store(StoreProjectRequest $request, SaveProject $save): RedirectResponse
    {
        $project = $save(new Project, $request->validated());

        return redirect()->route('admin.projects.edit', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('admin.projects.form', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, Project $project, SaveProject $save): RedirectResponse
    {
        $project = $save($project, $request->validated());

        return redirect()->route('admin.projects.edit', $project)->with('status', 'Project saved.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', 'Project moved to Trash.');
    }
}
