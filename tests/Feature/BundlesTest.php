<?php

beforeEach(function () {
    $this->withoutVite();
});

it('serves the public home page with the site bundle', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Site placeholder.');
});

it('serves the admin placeholder with the admin bundle', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('Admin placeholder.');
});

it('loads separate Vite entries for site and admin', function () {
    $site = file_get_contents(resource_path('views/site/layouts/app.blade.php'));
    $admin = file_get_contents(resource_path('views/admin/layouts/admin.blade.php'));

    expect($site)->toContain('resources/css/site.css')->not->toContain('admin.css')
        ->and($admin)->toContain('resources/css/admin.css')->not->toContain('site.css');
});

it('names the admin route with the admin prefix', function () {
    expect(route('admin.dashboard', absolute: false))->toBe('/admin');
});
