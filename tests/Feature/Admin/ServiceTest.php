<?php

use App\Models\Service;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validService(): array
{
    return ['title' => 'ERP advisory', 'is_published' => 1];
}

function makeService(array $attributes = []): Service
{
    return Service::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeService();

    foreach ([
        ['get', '/admin/services'], ['get', '/admin/services/create'], ['post', '/admin/services'],
        ['get', "/admin/services/{$record->id}/edit"], ['put', "/admin/services/{$record->id}"],
        ['delete', "/admin/services/{$record->id}"], ['get', '/admin/services/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeService(['title' => 'Alpha service']);
    makeService(['title' => 'Beta service']);

    $this->get('/admin/services')->assertOk()->assertSee('Alpha service')->assertSee('Beta service');
    $this->get('/admin/services?q=Alpha service')->assertOk()->assertSee('Alpha service')->assertDontSee('Beta service');

    Service::factory()->count(16)->create();
    expect(Service::count())->toBe(18);
    $this->get('/admin/services?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeService();

    $this->get('/admin/services/create')->assertOk()->assertSee('New');
    $this->get("/admin/services/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/services', validService())->assertRedirect('/admin/services')->assertSessionHas('status');

    expect(Service::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/services', ['title' => '', 'slug' => 'Bad Slug'])->assertSessionHasErrors(['title', 'slug']);

    expect(Service::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeService();

    $this->put("/admin/services/{$record->id}", array_merge(validService(), ['title' => 'Renamed service']))->assertRedirect('/admin/services');

    expect($record->fresh()->title)->toEqual('Renamed service');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeService(['title' => 'Trash me']);

    $this->delete("/admin/services/{$record->id}")->assertRedirect('/admin/services');
    expect(Service::find($record->id))->toBeNull();

    $this->get('/admin/services/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/services')->assertSee('Trash (1)');

    $this->post("/admin/services/{$record->id}/restore")->assertRedirect('/admin/services/trash');
    expect(Service::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/services/{$record->id}/force")->assertRedirect();
    expect(Service::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Service::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/services/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Service::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeService();

    $this->get('/admin/services')->assertSee('data-sortable', false);
    $this->get('/admin/services?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Service::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/services/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Service::published()->count())->toBe(0);

    $this->post('/admin/services/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Service::published()->count())->toBe(2);

    $this->post('/admin/services/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Service::count())->toBe(0)->and(Service::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeService(['title' => 'Alpha service']);
    makeService(['title' => 'Beta service', 'is_published' => false]);

    $this->get('/admin/services?state=draft')->assertSee('Beta service')->assertDontSee('Alpha service');
    $this->get('/admin/services?state=published')->assertSee('Alpha service')->assertDontSee('Beta service');
});

it('records changes in the activity log', function () {
    $record = makeService();
    $this->put("/admin/services/{$record->id}", array_merge(validService(), ['title' => 'Renamed service']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Service::class)->count())->toBeGreaterThan(0);
});
