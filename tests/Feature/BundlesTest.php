<?php

beforeEach(function () {
    $this->withoutVite();
});

it('serves the public home page with the site bundle', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Site placeholder.');
});

it('serves the admin login with the admin bundle only', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Sign in');
});

it('loads separate Vite entries for site and admin', function () {
    $site = file_get_contents(resource_path('views/site/layouts/app.blade.php'));
    $admin = file_get_contents(resource_path('views/admin/partials/head.blade.php'));

    expect($site)->toContain('resources/css/site.css')->not->toContain('admin.css')
        ->and($admin)->toContain('resources/css/admin.css')->not->toContain('site.css');
});

it('names the admin route with the admin prefix', function () {
    expect(route('admin.dashboard', absolute: false))->toBe('/admin')
        ->and(route('admin.login', absolute: false))->toBe('/admin/login');
});
