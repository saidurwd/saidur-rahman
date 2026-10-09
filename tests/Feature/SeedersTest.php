<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\DatabaseSeeder;

it('seeds the whole database cleanly', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Project::published()->count())->toBe(3)
        ->and(Experience::published()->count())->toBeGreaterThan(0)
        ->and(app(SettingService::class)->get('site_name'))->toBe('Saidur Rahman');
});

it('is safe to run twice without duplicating the admin user', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1);
});
