<?php

use App\Services\SettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    signInAsAdmin();
    Storage::fake('public');
});

function settingsPayload(array $overrides = []): array
{
    return array_merge([
        'site_name' => 'Saidur Rahman',
        'headline' => 'IT leader and builder',
        'contact_email' => 'hello@example.com',
        'availability_open' => 1,
        'availability_text' => 'Open to roles in Qatar',
        'linkedin' => 'https://www.linkedin.com/in/example',
    ], $overrides);
}

/** A JPEG with an EXIF block containing a marker that stands in for GPS data. */
function jpegWithExif(): UploadedFile
{
    $image = imagecreatetruecolor(1800, 1200);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    $payload = "Exif\0\0SECRET-GPS-COORDINATES";
    $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
    file_put_contents($path, substr($jpeg, 0, 2).$segment.substr($jpeg, 2));

    return new UploadedFile($path, 'portrait.jpg', 'image/jpeg', null, true);
}

it('keeps guests out', function () {
    auth()->logout();

    $this->flushSession()->get('/admin/settings')->assertRedirect('/admin/login');
    $this->flushSession()->put('/admin/settings', [])->assertRedirect('/admin/login');
});

it('shows the current values', function () {
    app(SettingService::class)->set('headline', 'Current headline');

    $this->get('/admin/settings')->assertOk()->assertSee('Current headline')->assertSee('Availability');
});

it('saves settings and the public layout picks them up straight away', function () {
    $this->put('/admin/settings', settingsPayload())->assertRedirect('/admin/settings')->assertSessionHas('status');

    $settings = app(SettingService::class);
    expect($settings->get('headline'))->toBe('IT leader and builder')
        ->and($settings->get('availability_open'))->toBeTrue()
        ->and($settings->get('linkedin'))->toBe('https://www.linkedin.com/in/example');

    $this->get('/')->assertSee('Open to roles in Qatar')->assertSee('https://www.linkedin.com/in/example');
});

it('clears optional values that are emptied', function () {
    $settings = app(SettingService::class);
    $settings->set('linkedin', 'https://www.linkedin.com/in/old');

    $this->put('/admin/settings', settingsPayload(['linkedin' => '', 'availability_open' => 0]))->assertRedirect();

    expect($settings->get('linkedin'))->toBeNull()->and($settings->get('availability_open'))->toBeFalse();
    $this->get('/')->assertDontSee('Open to roles in Qatar');
});

it('validates input', function () {
    $this->put('/admin/settings', settingsPayload(['site_name' => '', 'contact_email' => 'nope', 'linkedin' => 'http://insecure.example', 'seo_title' => str_repeat('a', 80)]))
        ->assertSessionHasErrors(['site_name', 'contact_email', 'linkedin', 'seo_title']);
});

it('stores the portrait as WebP with metadata removed and a capped width', function () {
    $upload = jpegWithExif();
    expect(file_get_contents($upload->getPathname()))->toContain('SECRET-GPS-COORDINATES');

    $this->put('/admin/settings', settingsPayload(['profile_photo' => $upload]))->assertRedirect();

    $path = app(SettingService::class)->get('profile_photo');
    expect($path)->toMatch('#^settings/profile_photo/[A-Za-z0-9]{32}\.webp$#');
    Storage::disk('public')->assertExists($path);

    $stored = Storage::disk('public')->get($path);
    expect($stored)->not->toContain('SECRET-GPS')->and(substr($stored, 8, 4))->toBe('WEBP');
    [$width] = getimagesizefromstring($stored);
    expect($width)->toBe(900);
});

it('replaces and removes images, deleting the old file', function () {
    $this->put('/admin/settings', settingsPayload(['og_image' => jpegWithExif()]));
    $first = app(SettingService::class)->get('og_image');

    $this->put('/admin/settings', settingsPayload(['og_image' => jpegWithExif()]));
    $second = app(SettingService::class)->get('og_image');
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->put('/admin/settings', settingsPayload(['remove_og_image' => 1]));
    expect(app(SettingService::class)->get('og_image'))->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

it('keeps existing images when no new file is sent', function () {
    $this->put('/admin/settings', settingsPayload(['profile_photo' => jpegWithExif()]));
    $path = app(SettingService::class)->get('profile_photo');

    $this->put('/admin/settings', settingsPayload(['headline' => 'Changed']));

    expect(app(SettingService::class)->get('profile_photo'))->toBe($path);
});

it('rejects non-images', function () {
    $this->put('/admin/settings', settingsPayload(['profile_photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))
        ->assertSessionHasErrors('profile_photo');
});

it('uses the share image as the default Open Graph image', function () {
    $this->put('/admin/settings', settingsPayload(['og_image' => jpegWithExif()]));
    $path = app(SettingService::class)->get('og_image');

    $this->get('/')->assertSee('property="og:image"', false)->assertSee(basename($path));
});
