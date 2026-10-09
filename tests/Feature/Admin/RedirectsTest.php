<?php

use App\Models\Redirect;

beforeEach(function () {
    signInAsAdmin();
});

it('keeps guests out', function () {
    auth()->logout();

    $this->flushSession()->get('/admin/redirects')->assertRedirect('/admin/login');
    $this->flushSession()->post('/admin/redirects', [])->assertRedirect('/admin/login');
});

it('lists and searches redirects', function () {
    Redirect::create(['from_path' => '/old-a', 'to_path' => '/new-a', 'code' => 301]);
    Redirect::create(['from_path' => '/old-b', 'to_path' => '/new-b', 'code' => 301]);

    $this->get('/admin/redirects')->assertOk()->assertSee('/old-a')->assertSee('/old-b');
    $this->get('/admin/redirects?q=old-a')->assertSee('/old-a')->assertDontSee('/old-b');
});

it('creates a redirect and normalises the paths', function () {
    $this->post('/admin/redirects', ['from_path' => 'old/page/?utm=1', 'to_path' => 'new-page', 'code' => 301])
        ->assertRedirect('/admin/redirects');

    $redirect = Redirect::firstOrFail();
    expect($redirect->from_path)->toBe('/old/page')
        ->and($redirect->to_path)->toBe('/new-page');
});

it('accepts a full external destination', function () {
    $this->post('/admin/redirects', ['from_path' => '/go', 'to_path' => 'https://example.com/x', 'code' => 302])->assertRedirect();

    expect(Redirect::first()->to_path)->toBe('https://example.com/x');
});

it('validates input', function (array $payload, string $field) {
    Redirect::create(['from_path' => '/taken', 'to_path' => '/x', 'code' => 301]);

    $this->post('/admin/redirects', $payload)->assertSessionHasErrors($field);
})->with([
    'missing from' => [['from_path' => '', 'to_path' => '/b', 'code' => 301], 'from_path'],
    'root as source' => [['from_path' => '/', 'to_path' => '/b', 'code' => 301], 'from_path'],
    'admin as source' => [['from_path' => '/admin/secret', 'to_path' => '/b', 'code' => 301], 'from_path'],
    'duplicate source' => [['from_path' => '/taken', 'to_path' => '/b', 'code' => 301], 'from_path'],
    'same source and destination' => [['from_path' => '/a', 'to_path' => '/a', 'code' => 301], 'to_path'],
    'javascript destination' => [['from_path' => '/a', 'to_path' => 'javascript:alert(1)', 'code' => 301], 'to_path'],
    'bad code' => [['from_path' => '/a', 'to_path' => '/b', 'code' => 200], 'code'],
]);

it('updates a redirect and keeps its own source valid', function () {
    $redirect = Redirect::create(['from_path' => '/old', 'to_path' => '/new', 'code' => 301]);

    $this->put("/admin/redirects/{$redirect->id}", ['from_path' => '/old', 'to_path' => '/newer', 'code' => 302])->assertRedirect('/admin/redirects');

    expect($redirect->fresh()->to_path)->toBe('/newer')->and($redirect->fresh()->code)->toBe(302);
});

it('deletes one redirect and bulk deletes several', function () {
    $a = Redirect::create(['from_path' => '/a', 'to_path' => '/x', 'code' => 301]);
    $b = Redirect::create(['from_path' => '/b', 'to_path' => '/x', 'code' => 301]);
    $c = Redirect::create(['from_path' => '/c', 'to_path' => '/x', 'code' => 301]);

    $this->delete("/admin/redirects/{$a->id}")->assertRedirect('/admin/redirects');
    $this->post('/admin/redirects/bulk', ['action' => 'delete', 'ids' => [$b->id, $c->id]])->assertRedirect();

    expect(Redirect::count())->toBe(0);
});

it('serves a created redirect on the public site', function () {
    $this->post('/admin/redirects', ['from_path' => '/old-cv', 'to_path' => '/cv', 'code' => 301]);

    $this->get('/old-cv')->assertStatus(301)->assertRedirect('/cv');
});
