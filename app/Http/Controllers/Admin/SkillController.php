<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSkillRequest;
use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Skill::class;
    }

    protected function routeBase(): string
    {
        return 'admin.skills';
    }

    protected function moduleLabel(): string
    {
        return 'Skills';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Skill::class);

        $filtered = $request->hasAny(['q', 'state', 'group']);

        return view('admin.skills.index', [
            'records' => Skill::query()
                ->with('group')
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->when($request->filled('group'), fn ($q) => $q->where('skill_group_id', $request->input('group')))
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => ! $filtered,
            'trashedCount' => $this->trashed()->count(),
            'groups' => SkillGroup::ordered()->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Skill::class);

        return view('admin.skills.form', ['record' => new Skill(['is_published' => true]), 'groups' => SkillGroup::ordered()->pluck('name', 'id')]);
    }

    public function store(SaveSkillRequest $request): RedirectResponse
    {
        Skill::create($request->validated() + ['sort_order' => Skill::nextSortOrder()]);

        return redirect()->route('admin.skills.index')->with('status', 'Skill created.');
    }

    public function edit(Skill $skill): View
    {
        $this->authorize('update', $skill);

        return view('admin.skills.form', ['record' => $skill, 'groups' => SkillGroup::ordered()->pluck('name', 'id')]);
    }

    public function update(SaveSkillRequest $request, Skill $skill): RedirectResponse
    {
        $skill->update($request->validated());

        return redirect()->route('admin.skills.index')->with('status', 'Skill saved.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);
        $skill->delete();

        return redirect()->route('admin.skills.index')->with('status', 'Skill moved to Trash.');
    }
}
