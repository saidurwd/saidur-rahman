<?php

use App\Models\Skill;
use App\Models\SkillGroup;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validSkill(): array
{
    return ['skill_group_id' => SkillGroup::factory()->create()->id, 'name' => 'Laravel', 'level' => 4, 'is_published' => 1];
}

function makeSkill(array $attributes = []): Skill
{
    return Skill::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeSkill();

    foreach ([
        ['get', '/admin/skills'], ['get', '/admin/skills/create'], ['post', '/admin/skills'],
        ['get', "/admin/skills/{$record->id}/edit"], ['put', "/admin/skills/{$record->id}"],
        ['delete', "/admin/skills/{$record->id}"], ['get', '/admin/skills/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeSkill(['name' => 'Alpha skill']);
    makeSkill(['name' => 'Beta skill']);

    $this->get('/admin/skills')->assertOk()->assertSee('Alpha skill')->assertSee('Beta skill');
    $this->get('/admin/skills?q=Alpha skill')->assertOk()->assertSee('Alpha skill')->assertDontSee('Beta skill');

    Skill::factory()->count(16)->create();
    expect(Skill::count())->toBe(18);
    $this->get('/admin/skills?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeSkill();

    $this->get('/admin/skills/create')->assertOk()->assertSee('New');
    $this->get("/admin/skills/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/skills', validSkill())->assertRedirect('/admin/skills')->assertSessionHas('status');

    expect(Skill::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/skills', ['skill_group_id' => 999, 'name' => '', 'level' => 9])->assertSessionHasErrors(['skill_group_id', 'name', 'level']);

    expect(Skill::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeSkill();

    $this->put("/admin/skills/{$record->id}", array_merge(validSkill(), ['name' => 'Renamed skill']))->assertRedirect('/admin/skills');

    expect($record->fresh()->name)->toEqual('Renamed skill');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeSkill(['name' => 'Trash me']);

    $this->delete("/admin/skills/{$record->id}")->assertRedirect('/admin/skills');
    expect(Skill::find($record->id))->toBeNull();

    $this->get('/admin/skills/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/skills')->assertSee('Trash (1)');

    $this->post("/admin/skills/{$record->id}/restore")->assertRedirect('/admin/skills/trash');
    expect(Skill::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/skills/{$record->id}/force")->assertRedirect();
    expect(Skill::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Skill::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/skills/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Skill::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeSkill();

    $this->get('/admin/skills')->assertSee('data-sortable', false);
    $this->get('/admin/skills?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Skill::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/skills/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Skill::published()->count())->toBe(0);

    $this->post('/admin/skills/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Skill::published()->count())->toBe(2);

    $this->post('/admin/skills/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Skill::count())->toBe(0)->and(Skill::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeSkill(['name' => 'Alpha skill']);
    makeSkill(['name' => 'Beta skill', 'is_published' => false]);

    $this->get('/admin/skills?state=draft')->assertSee('Beta skill')->assertDontSee('Alpha skill');
    $this->get('/admin/skills?state=published')->assertSee('Alpha skill')->assertDontSee('Beta skill');
});

it('records changes in the activity log', function () {
    $record = makeSkill();
    $this->put("/admin/skills/{$record->id}", array_merge(validSkill(), ['name' => 'Renamed skill']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Skill::class)->count())->toBeGreaterThan(0);
});
