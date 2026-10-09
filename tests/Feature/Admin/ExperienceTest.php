<?php

use App\Models\Experience;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validExperience(): array
{
    return ['company' => 'Acme Corp', 'role' => 'Head of IT', 'start_date' => '2018-01-01', 'is_published' => 1];
}

function makeExperience(array $attributes = []): Experience
{
    return Experience::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeExperience();

    foreach ([
        ['get', '/admin/experiences'], ['get', '/admin/experiences/create'], ['post', '/admin/experiences'],
        ['get', "/admin/experiences/{$record->id}/edit"], ['put', "/admin/experiences/{$record->id}"],
        ['delete', "/admin/experiences/{$record->id}"], ['get', '/admin/experiences/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeExperience(['company' => 'Alpha Corp']);
    makeExperience(['company' => 'Beta Corp']);

    $this->get('/admin/experiences')->assertOk()->assertSee('Alpha Corp')->assertSee('Beta Corp');
    $this->get('/admin/experiences?q=Alpha Corp')->assertOk()->assertSee('Alpha Corp')->assertDontSee('Beta Corp');

    Experience::factory()->count(16)->create();
    expect(Experience::count())->toBe(18);
    $this->get('/admin/experiences?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeExperience();

    $this->get('/admin/experiences/create')->assertOk()->assertSee('New');
    $this->get("/admin/experiences/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/experiences', validExperience())->assertRedirect('/admin/experiences')->assertSessionHas('status');

    expect(Experience::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/experiences', ['company' => '', 'role' => '', 'start_date' => 'nope'])->assertSessionHasErrors(['company', 'role', 'start_date']);

    expect(Experience::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeExperience();

    $this->put("/admin/experiences/{$record->id}", array_merge(validExperience(), ['role' => 'Chief Architect']))->assertRedirect('/admin/experiences');

    expect($record->fresh()->role)->toEqual('Chief Architect');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeExperience(['company' => 'Trash me']);

    $this->delete("/admin/experiences/{$record->id}")->assertRedirect('/admin/experiences');
    expect(Experience::find($record->id))->toBeNull();

    $this->get('/admin/experiences/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/experiences')->assertSee('Trash (1)');

    $this->post("/admin/experiences/{$record->id}/restore")->assertRedirect('/admin/experiences/trash');
    expect(Experience::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/experiences/{$record->id}/force")->assertRedirect();
    expect(Experience::withTrashed()->find($record->id))->toBeNull();
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Experience::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/experiences/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Experience::published()->count())->toBe(0);

    $this->post('/admin/experiences/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Experience::published()->count())->toBe(2);

    $this->post('/admin/experiences/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Experience::count())->toBe(0)->and(Experience::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeExperience(['company' => 'Alpha Corp']);
    makeExperience(['company' => 'Beta Corp', 'is_published' => false]);

    $this->get('/admin/experiences?state=draft')->assertSee('Beta Corp')->assertDontSee('Alpha Corp');
    $this->get('/admin/experiences?state=published')->assertSee('Alpha Corp')->assertDontSee('Beta Corp');
});

it('records changes in the activity log', function () {
    $record = makeExperience();
    $this->put("/admin/experiences/{$record->id}", array_merge(validExperience(), ['role' => 'Chief Architect']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Experience::class)->count())->toBeGreaterThan(0);
});
