<?php

use App\Models\Menu;
use App\Services\NavigationService;
use App\Services\SettingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the header menu from the menus table', function () {
    Menu::factory()->create(['label' => 'Work', 'url' => '/projects', 'location' => 'header', 'sort_order' => 1]);
    Menu::factory()->create(['label' => 'Hidden', 'url' => '/hidden', 'location' => 'header', 'is_published' => false]);
    Menu::factory()->create(['label' => 'Privacy', 'url' => '/privacy', 'location' => 'footer']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Work')
        ->assertDontSee('Hidden')
        ->assertSee('Privacy');
});

it('marks the current page in the navigation', function () {
    Menu::factory()->create(['label' => 'Home link', 'url' => '/', 'location' => 'header']);

    $this->get('/')->assertSee('aria-current="page"', false);
});

it('updates the menu when it changes in the admin', function () {
    $menu = Menu::factory()->create(['label' => 'Old', 'url' => '/old', 'location' => 'header']);
    $nav = app(NavigationService::class);

    expect(collect($nav->items('header'))->pluck('label')->all())->toBe(['Old']);

    $menu->update(['label' => 'New']);

    expect(collect($nav->items('header'))->pluck('label')->all())->toBe(['New']);
});

it('has the shell elements: skip link, landmarks, theme toggle and no-flash script', function () {
    $this->get('/')
        ->assertSee('Skip to content')
        ->assertSee('id="main"', false)
        ->assertSee('data-site-header', false)
        ->assertSee('data-site-footer', false)
        ->assertSee('x-data="themeToggle"', false)
        ->assertSee("localStorage.getItem('theme')", false)
        ->assertSee('aria-controls="mobile-menu"', false);
});

it('shows only the social links that are set, plus email and availability', function () {
    $settings = app(SettingService::class);
    $settings->set('linkedin', 'https://linkedin.com/in/example');
    $settings->set('github', null);
    $settings->set('contact_email', 'hello@example.com');
    $settings->set('availability_open', true);
    $settings->set('availability_text', 'Open to roles in Qatar');

    $this->get('/')
        ->assertSee('https://linkedin.com/in/example')
        ->assertDontSee('GitHub')
        ->assertSee('mailto:hello@example.com', false)
        ->assertSee('Open to roles in Qatar');
});

it('hides the availability chip when the toggle is off', function () {
    $settings = app(SettingService::class);
    $settings->set('availability_open', false);
    $settings->set('availability_text', 'Open to roles in Qatar');

    $this->get('/')->assertDontSee('Open to roles in Qatar');
});

it('serves the styleguide in local and testing only, with no indexing', function () {
    $this->get('/styleguide')
        ->assertOk()
        ->assertSee("The Architect's Ledger", false)
        ->assertSee('noindex', false)
        ->assertSee('Blueprint grid')
        ->assertSee('timeline-node', false);
});

it('renders the seeded site without errors', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')->assertOk()->assertSee('About')->assertSee('Contact');
});

it('renders the seo component with defaults, canonical, open graph and twitter tags', function () {
    app(SettingService::class)->setMany([
        'site_name' => 'Saidur Rahman',
        'seo_description' => 'Default description',
    ]);

    $html = Blade::render('<x-site.seo title="Projects" />');

    expect($html)
        ->toContain('<title>Projects · Saidur Rahman</title>')
        ->toContain('<meta name="description" content="Default description">')
        ->toContain('<link rel="canonical" href="'.url()->current().'">')
        ->toContain('property="og:title" content="Projects"')
        ->toContain('name="twitter:card" content="summary"')
        ->not->toContain('noindex');
});

it('uses the default seo title without a page title and honours overrides', function () {
    app(SettingService::class)->setMany(['site_name' => 'Saidur Rahman', 'seo_title' => 'Saidur Rahman — Head of IT']);

    $html = Blade::render('<x-site.seo description="Custom" canonical="https://example.com/x" image="https://example.com/i.png" type="article" published-at="2026-01-01" :noindex="true" />');

    expect($html)
        ->toContain('<title>Saidur Rahman — Head of IT</title>')
        ->toContain('content="Custom"')
        ->toContain('href="https://example.com/x"')
        ->toContain('og:image" content="https://example.com/i.png"')
        ->toContain('twitter:card" content="summary_large_image"')
        ->toContain('article:published_time')
        ->toContain('noindex');
});

it('emits JSON-LD that cannot break out of the script tag', function () {
    $html = Blade::render('<x-site.seo :schema="$schema" />', [
        'schema' => ['@context' => 'https://schema.org', '@type' => 'Person', 'name' => 'A </script><script>alert(1)</script>'],
    ]);

    expect($html)
        ->toContain('application/ld+json')
        ->toContain('"@type":"Person"')
        ->not->toContain('</script><script>alert');
});

it('supports several JSON-LD blocks', function () {
    $html = Blade::render('<x-site.seo :schema="$schema" />', [
        'schema' => [['@type' => 'Person'], ['@type' => 'BreadcrumbList']],
    ]);

    expect(substr_count($html, 'application/ld+json'))->toBe(2);
});

it('escapes seo values', function () {
    $html = Blade::render('<x-site.seo title=\'"><script>x</script>\' />');

    expect($html)->not->toContain('<script>x</script>');
});
