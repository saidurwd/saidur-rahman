<?php

use App\Models\SkillGroup;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validSkillGroup(): array
{
    return ['name' => 'Engineering', 'is_published' => 1];
}

function makeSkillGroup(array $attributes = []): SkillGroup
{
    return SkillGroup::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeSkillGroup();

    foreach ([
        ['get', '/admin/skill-groups'], ['get', '/admin/skill-groups/create'], ['post', '/admin/skill-groups'],
        ['get', "/admin/skill-groups/{$record->id}/edit"], ['put', "/admin/skill-groups/{$record->id}"],
        ['delete', "/admin/skill-groups/{$record->id}"], ['get', '/admin/skill-groups/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeSkillGroup(['name' => 'Alpha group']);
    makeSkillGroup(['name' => 'Beta group']);

    $this->get('/admin/skill-groups')->assertOk()->assertSee('Alpha group')->assertSee('Beta group');
    $this->get('/admin/skill-groups?q=Alpha group')->assertOk()->assertSee('Alpha group')->assertDontSee('Beta group');

    SkillGroup::factory()->count(16)->create();
    expect(SkillGroup::count())->toBe(18);
    $this->get('/admin/skill-groups?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeSkillGroup();

    $this->get('/admin/skill-groups/create')->assertOk()->assertSee('New');
    $this->get("/admin/skill-groups/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/skill-groups', validSkillGroup())->assertRedirect('/admin/skill-groups')->assertSessionHas('status');

    expect(SkillGroup::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/skill-groups', ['name' => ''])->assertSessionHasErrors(['name']);

    expect(SkillGroup::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeSkillGroup();

    $this->put("/admin/skill-groups/{$record->id}", array_merge(validSkillGroup(), ['name' => 'Renamed group']))->assertRedirect('/admin/skill-groups');

    expect($record->fresh()->name)->toEqual('Renamed group');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeSkillGroup(['name' => 'Trash me']);

    $this->delete("/admin/skill-groups/{$record->id}")->assertRedirect('/admin/skill-groups');
    expect(SkillGroup::find($record->id))->toBeNull();

    $this->get('/admin/skill-groups/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/skill-groups')->assertSee('Trash (1)');

    $this->post("/admin/skill-groups/{$record->id}/restore")->assertRedirect('/admin/skill-groups/trash');
    expect(SkillGroup::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/skill-groups/{$record->id}/force")->assertRedirect();
    expect(SkillGroup::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = SkillGroup::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/skill-groups/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(SkillGroup::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeSkillGroup();

    $this->get('/admin/skill-groups')->assertSee('data-sortable', false);
    $this->get('/admin/skill-groups?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = SkillGroup::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/skill-groups/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(SkillGroup::published()->count())->toBe(0);

    $this->post('/admin/skill-groups/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(SkillGroup::published()->count())->toBe(2);

    $this->post('/admin/skill-groups/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(SkillGroup::count())->toBe(0)->and(SkillGroup::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeSkillGroup(['name' => 'Alpha group']);
    makeSkillGroup(['name' => 'Beta group', 'is_published' => false]);

    $this->get('/admin/skill-groups?state=draft')->assertSee('Beta group')->assertDontSee('Alpha group');
    $this->get('/admin/skill-groups?state=published')->assertSee('Alpha group')->assertDontSee('Beta group');
});

it('records changes in the activity log', function () {
    $record = makeSkillGroup();
    $this->put("/admin/skill-groups/{$record->id}", array_merge(validSkillGroup(), ['name' => 'Renamed group']))->assertRedirect();

    expect(Activity::query()->where('subject_type', SkillGroup::class)->count())->toBeGreaterThan(0);
});
