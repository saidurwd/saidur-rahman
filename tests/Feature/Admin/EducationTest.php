<?php

use App\Models\Education;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validEducation(): array
{
    return ['institution' => 'Sample University', 'degree' => 'BSc', 'start_year' => 2000, 'end_year' => 2004, 'is_published' => 1];
}

function makeEducation(array $attributes = []): Education
{
    return Education::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeEducation();

    foreach ([
        ['get', '/admin/educations'], ['get', '/admin/educations/create'], ['post', '/admin/educations'],
        ['get', "/admin/educations/{$record->id}/edit"], ['put', "/admin/educations/{$record->id}"],
        ['delete', "/admin/educations/{$record->id}"], ['get', '/admin/educations/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeEducation(['institution' => 'Alpha University']);
    makeEducation(['institution' => 'Beta University']);

    $this->get('/admin/educations')->assertOk()->assertSee('Alpha University')->assertSee('Beta University');
    $this->get('/admin/educations?q=Alpha University')->assertOk()->assertSee('Alpha University')->assertDontSee('Beta University');

    Education::factory()->count(16)->create();
    expect(Education::count())->toBe(18);
    $this->get('/admin/educations?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeEducation();

    $this->get('/admin/educations/create')->assertOk()->assertSee('New');
    $this->get("/admin/educations/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/educations', validEducation())->assertRedirect('/admin/educations')->assertSessionHas('status');

    expect(Education::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/educations', ['institution' => '', 'degree' => '', 'start_year' => 'abc'])->assertSessionHasErrors(['institution', 'degree', 'start_year']);

    expect(Education::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeEducation();

    $this->put("/admin/educations/{$record->id}", array_merge(validEducation(), ['degree' => 'MSc']))->assertRedirect('/admin/educations');

    expect($record->fresh()->degree)->toEqual('MSc');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeEducation(['institution' => 'Trash me']);

    $this->delete("/admin/educations/{$record->id}")->assertRedirect('/admin/educations');
    expect(Education::find($record->id))->toBeNull();

    $this->get('/admin/educations/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/educations')->assertSee('Trash (1)');

    $this->post("/admin/educations/{$record->id}/restore")->assertRedirect('/admin/educations/trash');
    expect(Education::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/educations/{$record->id}/force")->assertRedirect();
    expect(Education::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Education::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/educations/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Education::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeEducation();

    $this->get('/admin/educations')->assertSee('data-sortable', false);
    $this->get('/admin/educations?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Education::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/educations/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Education::published()->count())->toBe(0);

    $this->post('/admin/educations/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Education::published()->count())->toBe(2);

    $this->post('/admin/educations/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Education::count())->toBe(0)->and(Education::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeEducation(['institution' => 'Alpha University']);
    makeEducation(['institution' => 'Beta University', 'is_published' => false]);

    $this->get('/admin/educations?state=draft')->assertSee('Beta University')->assertDontSee('Alpha University');
    $this->get('/admin/educations?state=published')->assertSee('Alpha University')->assertDontSee('Beta University');
});

it('records changes in the activity log', function () {
    $record = makeEducation();
    $this->put("/admin/educations/{$record->id}", array_merge(validEducation(), ['degree' => 'MSc']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Education::class)->count())->toBeGreaterThan(0);
});
