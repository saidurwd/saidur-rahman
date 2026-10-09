<?php

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Support\Facades\Cache;

it('returns a default for unknown keys', function () {
    expect(app(SettingService::class)->get('missing', 'fallback'))->toBe('fallback');
});

it('stores and reads values of any json type', function () {
    $settings = app(SettingService::class);

    $settings->set('headline', 'Head of IT');
    $settings->set('socials', ['linkedin' => 'https://example.com'], 'social');
    $settings->set('open', true);

    expect($settings->get('headline'))->toBe('Head of IT')
        ->and($settings->get('socials'))->toBe(['linkedin' => 'https://example.com'])
        ->and($settings->get('open'))->toBeTrue()
        ->and($settings->group('social'))->toHaveKey('socials');
});

it('caches all settings and clears the cache on save', function () {
    $settings = app(SettingService::class);
    $settings->set('headline', 'One');

    expect($settings->get('headline'))->toBe('One')
        ->and(Cache::has(SettingService::CACHE_KEY))->toBeTrue();

    $settings->set('headline', 'Two');

    expect(Cache::has(SettingService::CACHE_KEY))->toBeFalse()
        ->and($settings->get('headline'))->toBe('Two');
});

it('clears the cache when a setting is deleted', function () {
    $settings = app(SettingService::class);
    $settings->set('headline', 'One');
    $settings->get('headline');

    Setting::query()->where('key', 'headline')->first()->delete();

    expect($settings->get('headline'))->toBeNull();
});

it('updates many values in one call', function () {
    $settings = app(SettingService::class);
    $settings->setMany(['a' => 1, 'b' => 2], 'group');

    expect($settings->group('group'))->toBe(['a' => 1, 'b' => 2]);
});
