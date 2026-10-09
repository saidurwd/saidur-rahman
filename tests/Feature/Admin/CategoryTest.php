<?php

use App\Models\Category;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validCategory(): array
{
    return ['name' => 'IT leadership'];
}

function makeCategory(array $attributes = []): Category
{
    return Category::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeCategory();

    foreach ([
        ['get', '/admin/categories'], ['get', '/admin/categories/create'], ['post', '/admin/categories'],
        ['get', "/admin/categories/{$record->id}/edit"], ['put', "/admin/categories/{$record->id}"],
        ['delete', "/admin/categories/{$record->id}"], ['get', '/admin/categories/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeCategory(['name' => 'Alpha category']);
    makeCategory(['name' => 'Beta category']);

    $this->get('/admin/categories')->assertOk()->assertSee('Alpha category')->assertSee('Beta category');
    $this->get('/admin/categories?q=Alpha category')->assertOk()->assertSee('Alpha category')->assertDontSee('Beta category');

    Category::factory()->count(16)->create();
    expect(Category::count())->toBe(18);
    $this->get('/admin/categories?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeCategory();

    $this->get('/admin/categories/create')->assertOk()->assertSee('New');
    $this->get("/admin/categories/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/categories', validCategory())->assertRedirect('/admin/categories')->assertSessionHas('status');

    expect(Category::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/categories', ['name' => '', 'slug' => 'Bad Slug'])->assertSessionHasErrors(['name', 'slug']);

    expect(Category::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeCategory();

    $this->put("/admin/categories/{$record->id}", array_merge(validCategory(), ['name' => 'Renamed category']))->assertRedirect('/admin/categories');

    expect($record->fresh()->name)->toEqual('Renamed category');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeCategory(['name' => 'Trash me']);

    $this->delete("/admin/categories/{$record->id}")->assertRedirect('/admin/categories');
    expect(Category::find($record->id))->toBeNull();

    $this->get('/admin/categories/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/categories')->assertSee('Trash (1)');

    $this->post("/admin/categories/{$record->id}/restore")->assertRedirect('/admin/categories/trash');
    expect(Category::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/categories/{$record->id}/force")->assertRedirect();
    expect(Category::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Category::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/categories/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Category::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeCategory();

    $this->get('/admin/categories')->assertSee('data-sortable', false);
    $this->get('/admin/categories?q=zzz')->assertDontSee('data-sortable', false);
});

it('records changes in the activity log', function () {
    $record = makeCategory();
    $this->put("/admin/categories/{$record->id}", array_merge(validCategory(), ['name' => 'Renamed category']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Category::class)->count())->toBeGreaterThan(0);
});
