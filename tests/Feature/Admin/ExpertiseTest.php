<?php

use App\Models\Expertise;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validExpertise(): array
{
    return ['title' => 'ERP advisory', 'is_published' => 1];
}

function makeExpertise(array $attributes = []): Expertise
{
    return Expertise::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeExpertise();

    foreach ([
        ['get', '/admin/expertise'], ['get', '/admin/expertise/create'], ['post', '/admin/expertise'],
        ['get', "/admin/expertise/{$record->id}/edit"], ['put', "/admin/expertise/{$record->id}"],
        ['delete', "/admin/expertise/{$record->id}"], ['get', '/admin/expertise/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeExpertise(['title' => 'Alpha expertise']);
    makeExpertise(['title' => 'Beta expertise']);

    $this->get('/admin/expertise')->assertOk()->assertSee('Alpha expertise')->assertSee('Beta expertise');
    $this->get('/admin/expertise?q=Alpha expertise')->assertOk()->assertSee('Alpha expertise')->assertDontSee('Beta expertise');

    Expertise::factory()->count(16)->create();
    expect(Expertise::count())->toBe(18);
    $this->get('/admin/expertise?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeExpertise();

    $this->get('/admin/expertise/create')->assertOk()->assertSee('New');
    $this->get("/admin/expertise/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/expertise', validExpertise())->assertRedirect('/admin/expertise')->assertSessionHas('status');

    expect(Expertise::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/expertise', ['title' => '', 'slug' => 'Bad Slug'])->assertSessionHasErrors(['title', 'slug']);

    expect(Expertise::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeExpertise();

    $this->put("/admin/expertise/{$record->id}", array_merge(validExpertise(), ['title' => 'Renamed expertise']))->assertRedirect('/admin/expertise');

    expect($record->fresh()->title)->toEqual('Renamed expertise');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeExpertise(['title' => 'Trash me']);

    $this->delete("/admin/expertise/{$record->id}")->assertRedirect('/admin/expertise');
    expect(Expertise::find($record->id))->toBeNull();

    $this->get('/admin/expertise/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/expertise')->assertSee('Trash (1)');

    $this->post("/admin/expertise/{$record->id}/restore")->assertRedirect('/admin/expertise/trash');
    expect(Expertise::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/expertise/{$record->id}/force")->assertRedirect();
    expect(Expertise::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Expertise::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/expertise/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Expertise::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeExpertise();

    $this->get('/admin/expertise')->assertSee('data-sortable', false);
    $this->get('/admin/expertise?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Expertise::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/expertise/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Expertise::published()->count())->toBe(0);

    $this->post('/admin/expertise/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Expertise::published()->count())->toBe(2);

    $this->post('/admin/expertise/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Expertise::count())->toBe(0)->and(Expertise::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeExpertise(['title' => 'Alpha expertise']);
    makeExpertise(['title' => 'Beta expertise', 'is_published' => false]);

    $this->get('/admin/expertise?state=draft')->assertSee('Beta expertise')->assertDontSee('Alpha expertise');
    $this->get('/admin/expertise?state=published')->assertSee('Alpha expertise')->assertDontSee('Beta expertise');
});

it('records changes in the activity log', function () {
    $record = makeExpertise();
    $this->put("/admin/expertise/{$record->id}", array_merge(validExpertise(), ['title' => 'Renamed expertise']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Expertise::class)->count())->toBeGreaterThan(0);
});
