<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSkillGroupRequest;
use App\Models\SkillGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillGroupController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return SkillGroup::class;
    }

    protected function routeBase(): string
    {
        return 'admin.skill-groups';
    }

    protected function moduleLabel(): string
    {
        return 'Skill groups';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SkillGroup::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.skill-groups.index', [
            'records' => SkillGroup::query()
                ->withCount('skills')
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
        $this->authorize('create', SkillGroup::class);

        return view('admin.skill-groups.form', ['record' => new SkillGroup(['is_published' => true])]);
    }

    public function store(SaveSkillGroupRequest $request): RedirectResponse
    {
        SkillGroup::create($request->validated() + ['sort_order' => SkillGroup::nextSortOrder()]);

        return redirect()->route('admin.skill-groups.index')->with('status', 'Skill group created.');
    }

    public function edit(SkillGroup $skill_group): View
    {
        $this->authorize('update', $skill_group);

        return view('admin.skill-groups.form', ['record' => $skill_group]);
    }

    public function update(SaveSkillGroupRequest $request, SkillGroup $skill_group): RedirectResponse
    {
        $skill_group->update($request->validated());

        return redirect()->route('admin.skill-groups.index')->with('status', 'Skill group saved.');
    }

    public function destroy(SkillGroup $skill_group): RedirectResponse
    {
        $this->authorize('delete', $skill_group);
        $skill_group->delete();

        return redirect()->route('admin.skill-groups.index')->with('status', 'Skill group moved to Trash.');
    }
}
