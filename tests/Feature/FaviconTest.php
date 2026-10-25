<?php

use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;

it('falls back to the SVG favicon without a profile photo', function () {
    $this->get('/favicon.png')->assertRedirect('/favicon.svg');
    $this->get('/')->assertSee('favicon.svg')->assertDontSee('favicon.png');
});

it('cuts the favicon and touch icon from the profile photo', function () {
    Storage::fake('public');
    $image = imagecreatetruecolor(400, 500);
    ob_start();
    imagewebp($image);
    Storage::disk('public')->put('settings/profile_photo/me.webp', (string) ob_get_clean());
    app(SettingService::class)->set('profile_photo', 'settings/profile_photo/me.webp');

    foreach (['/favicon.png' => 64, '/apple-touch-icon.png' => 180] as $url => $size) {
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        [$width, $height] = getimagesizefromstring($response->getContent());
        expect([$width, $height])->toBe([$size, $size]);
    }

    $this->get('/')->assertSee('/favicon.png')->assertSee('/apple-touch-icon.png');
});
