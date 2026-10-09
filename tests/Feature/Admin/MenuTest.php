<?php

use App\Models\Menu;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validMenu(): array
{
    return ['label' => 'Work', 'url' => '/projects', 'location' => 'header', 'is_published' => 1];
}

function makeMenu(array $attributes = []): Menu
{
    return Menu::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeMenu();

    foreach ([
        ['get', '/admin/menus'], ['get', '/admin/menus/create'], ['post', '/admin/menus'],
        ['get', "/admin/menus/{$record->id}/edit"], ['put', "/admin/menus/{$record->id}"],
        ['delete', "/admin/menus/{$record->id}"], ['get', '/admin/menus/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeMenu(['label' => 'Alpha link']);
    makeMenu(['label' => 'Beta link']);

    $this->get('/admin/menus')->assertOk()->assertSee('Alpha link')->assertSee('Beta link');
    $this->get('/admin/menus?q=Alpha link')->assertOk()->assertSee('Alpha link')->assertDontSee('Beta link');

    Menu::factory()->count(16)->create();
    expect(Menu::count())->toBe(18);
    $this->get('/admin/menus?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeMenu();

    $this->get('/admin/menus/create')->assertOk()->assertSee('New');
    $this->get("/admin/menus/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/menus', validMenu())->assertRedirect('/admin/menus')->assertSessionHas('status');

    expect(Menu::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/menus', ['label' => '', 'location' => 'sidebar'])->assertSessionHasErrors(['label', 'location', 'url']);

    expect(Menu::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeMenu();

    $this->put("/admin/menus/{$record->id}", array_merge(validMenu(), ['label' => 'Renamed link']))->assertRedirect('/admin/menus');

    expect($record->fresh()->label)->toEqual('Renamed link');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeMenu(['label' => 'Trash me']);

    $this->delete("/admin/menus/{$record->id}")->assertRedirect('/admin/menus');
    expect(Menu::find($record->id))->toBeNull();

    $this->get('/admin/menus/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/menus')->assertSee('Trash (1)');

    $this->post("/admin/menus/{$record->id}/restore")->assertRedirect('/admin/menus/trash');
    expect(Menu::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/menus/{$record->id}/force")->assertRedirect();
    expect(Menu::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Menu::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/menus/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Menu::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeMenu();

    $this->get('/admin/menus')->assertSee('data-sortable', false);
    $this->get('/admin/menus?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Menu::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/menus/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Menu::published()->count())->toBe(0);

    $this->post('/admin/menus/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Menu::published()->count())->toBe(2);

    $this->post('/admin/menus/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Menu::count())->toBe(0)->and(Menu::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeMenu(['label' => 'Alpha link']);
    makeMenu(['label' => 'Beta link', 'is_published' => false]);

    $this->get('/admin/menus?state=draft')->assertSee('Beta link')->assertDontSee('Alpha link');
    $this->get('/admin/menus?state=published')->assertSee('Alpha link')->assertDontSee('Beta link');
});

it('records changes in the activity log', function () {
    $record = makeMenu();
    $this->put("/admin/menus/{$record->id}", array_merge(validMenu(), ['label' => 'Renamed link']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Menu::class)->count())->toBeGreaterThan(0);
});
