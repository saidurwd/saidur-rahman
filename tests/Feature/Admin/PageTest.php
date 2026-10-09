<?php

use App\Models\Page;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validPage(): array
{
    return ['title' => 'Privacy policy', 'is_published' => 1];
}

function makePage(array $attributes = []): Page
{
    return Page::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makePage();

    foreach ([
        ['get', '/admin/pages'], ['get', '/admin/pages/create'], ['post', '/admin/pages'],
        ['get', "/admin/pages/{$record->id}/edit"], ['put', "/admin/pages/{$record->id}"],
        ['delete', "/admin/pages/{$record->id}"], ['get', '/admin/pages/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makePage(['title' => 'Alpha page']);
    makePage(['title' => 'Beta page']);

    $this->get('/admin/pages')->assertOk()->assertSee('Alpha page')->assertSee('Beta page');
    $this->get('/admin/pages?q=Alpha page')->assertOk()->assertSee('Alpha page')->assertDontSee('Beta page');

    Page::factory()->count(16)->create();
    expect(Page::count())->toBe(18);
    $this->get('/admin/pages?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makePage();

    $this->get('/admin/pages/create')->assertOk()->assertSee('New');
    $this->get("/admin/pages/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/pages', validPage())->assertRedirect('/admin/pages')->assertSessionHas('status');

    expect(Page::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/pages', ['title' => '', 'slug' => 'admin'])->assertSessionHasErrors(['title', 'slug']);

    expect(Page::count())->toBe(0);
});

it('updates a record', function () {
    $record = makePage();

    $this->put("/admin/pages/{$record->id}", array_merge(validPage(), ['title' => 'Renamed page']))->assertRedirect('/admin/pages');

    expect($record->fresh()->title)->toEqual('Renamed page');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makePage(['title' => 'Trash me']);

    $this->delete("/admin/pages/{$record->id}")->assertRedirect('/admin/pages');
    expect(Page::find($record->id))->toBeNull();

    $this->get('/admin/pages/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/pages')->assertSee('Trash (1)');

    $this->post("/admin/pages/{$record->id}/restore")->assertRedirect('/admin/pages/trash');
    expect(Page::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/pages/{$record->id}/force")->assertRedirect();
    expect(Page::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Page::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/pages/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Page::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makePage();

    $this->get('/admin/pages')->assertSee('data-sortable', false);
    $this->get('/admin/pages?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Page::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/pages/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Page::published()->count())->toBe(0);

    $this->post('/admin/pages/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Page::published()->count())->toBe(2);

    $this->post('/admin/pages/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Page::count())->toBe(0)->and(Page::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makePage(['title' => 'Alpha page']);
    makePage(['title' => 'Beta page', 'is_published' => false]);

    $this->get('/admin/pages?state=draft')->assertSee('Beta page')->assertDontSee('Alpha page');
    $this->get('/admin/pages?state=published')->assertSee('Alpha page')->assertDontSee('Beta page');
});

it('records changes in the activity log', function () {
    $record = makePage();
    $this->put("/admin/pages/{$record->id}", array_merge(validPage(), ['title' => 'Renamed page']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Page::class)->count())->toBeGreaterThan(0);
});
