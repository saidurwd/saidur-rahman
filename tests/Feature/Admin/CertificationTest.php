<?php

use App\Models\Certification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validCertification(): array
{
    return ['name' => 'Sample Certification', 'issuer' => 'Sample Issuer', 'is_published' => 1];
}

function makeCertification(array $attributes = []): Certification
{
    return Certification::factory()->create($attributes);
}

it('keeps guests out', function () {
    auth()->logout();
    $record = makeCertification();

    foreach ([
        ['get', '/admin/certifications'], ['get', '/admin/certifications/create'], ['post', '/admin/certifications'],
        ['get', "/admin/certifications/{$record->id}/edit"], ['put', "/admin/certifications/{$record->id}"],
        ['delete', "/admin/certifications/{$record->id}"], ['get', '/admin/certifications/trash'],
    ] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

it('lists, searches and paginates', function () {
    makeCertification(['name' => 'Alpha Cert']);
    makeCertification(['name' => 'Beta Cert']);

    $this->get('/admin/certifications')->assertOk()->assertSee('Alpha Cert')->assertSee('Beta Cert');
    $this->get('/admin/certifications?q=Alpha Cert')->assertOk()->assertSee('Alpha Cert')->assertDontSee('Beta Cert');

    Certification::factory()->count(16)->create();
    expect(Certification::count())->toBe(18);
    $this->get('/admin/certifications?page=2')->assertOk();
});

it('renders the create and edit forms', function () {
    $record = makeCertification();

    $this->get('/admin/certifications/create')->assertOk()->assertSee('New');
    $this->get("/admin/certifications/{$record->id}/edit")->assertOk()->assertSee('Edit');
});

it('creates a record', function () {
    $this->post('/admin/certifications', validCertification())->assertRedirect('/admin/certifications')->assertSessionHas('status');

    expect(Certification::count())->toBe(1);
});

it('validates input on create', function () {
    $this->post('/admin/certifications', ['name' => '', 'issuer' => '', 'credential_url' => 'javascript:alert(1)'])->assertSessionHasErrors(['name', 'issuer', 'credential_url']);

    expect(Certification::count())->toBe(0);
});

it('updates a record', function () {
    $record = makeCertification();

    $this->put("/admin/certifications/{$record->id}", array_merge(validCertification(), ['issuer' => 'New Issuer']))->assertRedirect('/admin/certifications');

    expect($record->fresh()->issuer)->toEqual('New Issuer');
});

it('soft deletes, shows in trash, restores and force deletes', function () {
    $record = makeCertification(['name' => 'Trash me']);

    $this->delete("/admin/certifications/{$record->id}")->assertRedirect('/admin/certifications');
    expect(Certification::find($record->id))->toBeNull();

    $this->get('/admin/certifications/trash')->assertOk()->assertSee('Trash me');
    $this->get('/admin/certifications')->assertSee('Trash (1)');

    $this->post("/admin/certifications/{$record->id}/restore")->assertRedirect('/admin/certifications/trash');
    expect(Certification::find($record->id))->not->toBeNull();

    $record->delete();
    $this->delete("/admin/certifications/{$record->id}/force")->assertRedirect();
    expect(Certification::withTrashed()->find($record->id))->toBeNull();
});
it('reorders records', function () {
    [$a, $b, $c] = Certification::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

    $this->postJson('/admin/certifications/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

    expect(Certification::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('only allows drag reordering when the list is not filtered', function () {
    makeCertification();

    $this->get('/admin/certifications')->assertSee('data-sortable', false);
    $this->get('/admin/certifications?q=zzz')->assertDontSee('data-sortable', false);
});
it('bulk publishes, unpublishes and trashes', function () {
    $ids = Certification::factory()->count(2)->create()->pluck('id')->all();

    $this->post('/admin/certifications/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
    expect(Certification::published()->count())->toBe(0);

    $this->post('/admin/certifications/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
    expect(Certification::published()->count())->toBe(2);

    $this->post('/admin/certifications/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
    expect(Certification::count())->toBe(0)->and(Certification::onlyTrashed()->count())->toBe(2);
});

it('filters by published state', function () {
    makeCertification(['name' => 'Alpha Cert']);
    makeCertification(['name' => 'Beta Cert', 'is_published' => false]);

    $this->get('/admin/certifications?state=draft')->assertSee('Beta Cert')->assertDontSee('Alpha Cert');
    $this->get('/admin/certifications?state=published')->assertSee('Alpha Cert')->assertDontSee('Beta Cert');
});

it('records changes in the activity log', function () {
    $record = makeCertification();
    $this->put("/admin/certifications/{$record->id}", array_merge(validCertification(), ['issuer' => 'New Issuer']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Certification::class)->count())->toBeGreaterThan(0);
});
