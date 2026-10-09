<?php

use App\Models\Testimonial;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validTestimonial(): array
{
    return ['name' => 'Jane Doe', 'quote' => 'Great to work with.', 'is_approved' => 1];
}

function makeTestimonial(array $attributes = []): Testimonial
{
    return Testimonial::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeTestimonial();

    foreach ([
        ['get', '/admin/testimonials'], ['get', '/admin/testimonials/create'], ['post', '/admin/testimonials'],
        ['get', "/admin/testimonials/{$record->id}/edit"], ['put', "/admin/testimonials/{$record->id}"],
        ['delete', "/admin/testimonials/{$record->id}"], ['get', '/admin/testimonials/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeTestimonial(['name' => 'Alpha Person']);
    makeTestimonial(['name' => 'Beta Person']);

    $this->get('/admin/testimonials')->assertOk()->assertSee('Alpha Person')->assertSee('Beta Person');
    $this->get('/admin/testimonials?q=Alpha Person')->assertOk()->assertSee('Alpha Person')->assertDontSee('Beta Person');

    Testimonial::factory()->count(16)->create();
    expect(Testimonial::count())->toBe(18);
    $this->get('/admin/testimonials?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeTestimonial();

    $this->get('/admin/testimonials/create')->assertOk()->assertSee('New');
    $this->get("/admin/testimonials/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/testimonials', validTestimonial())->assertRedirect('/admin/testimonials')->assertSessionHas('status');

    expect(Testimonial::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/testimonials', ['name' => '', 'quote' => ''])->assertSessionHasErrors(['name', 'quote']);

    expect(Testimonial::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeTestimonial();

    $this->put("/admin/testimonials/{$record->id}", array_merge(validTestimonial(), ['company' => 'New Co']))->assertRedirect('/admin/testimonials');

    expect($record->fresh()->company)->toEqual('New Co');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeTestimonial(['name' => 'Trash me']);

    $this->delete("/admin/testimonials/{$record->id}")->assertRedirect('/admin/testimonials');
    expect(Testimonial::find($record->id))->toBeNull();

    $this->get('/admin/testimonials/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/testimonials')->assertSee('Trash (1)');

    $this->post("/admin/testimonials/{$record->id}/restore")->assertRedirect('/admin/testimonials/trash');
    expect(Testimonial::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/testimonials/{$record->id}/force")->assertRedirect();
    expect(Testimonial::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Testimonial::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/testimonials/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Testimonial::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeTestimonial();

    $this->get('/admin/testimonials')->assertSee('data-sortable', false);
    $this->get('/admin/testimonials?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Testimonial::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/testimonials/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Testimonial::published()->count())->toBe(0);

    $this->post('/admin/testimonials/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Testimonial::published()->count())->toBe(2);

    $this->post('/admin/testimonials/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Testimonial::count())->toBe(0)->and(Testimonial::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeTestimonial(['name' => 'Alpha Person']);
    makeTestimonial(['name' => 'Beta Person', 'is_approved' => false]);

    $this->get('/admin/testimonials?state=draft')->assertSee('Beta Person')->assertDontSee('Alpha Person');
    $this->get('/admin/testimonials?state=published')->assertSee('Alpha Person')->assertDontSee('Beta Person');
});

it('records changes in the activity log', function () {
    $record = makeTestimonial();
    $this->put("/admin/testimonials/{$record->id}", array_merge(validTestimonial(), ['company' => 'New Co']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Testimonial::class)->count())->toBeGreaterThan(0);
});
