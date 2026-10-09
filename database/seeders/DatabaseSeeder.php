<?php

namespace Database\Seeders;

use App\Services\CacheService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingsSeeder::class,
            ProfileContentSeeder::class,
            ProjectSeeder::class,
            BlogSeeder::class,
            SiteSeeder::class,
        ]);

        app(CacheService::class)->flush();
    }
}
