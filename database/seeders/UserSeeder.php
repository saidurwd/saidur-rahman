<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seeds the single admin. Set ADMIN_EMAIL / ADMIN_PASSWORD in .env to choose them. Without ADMIN_PASSWORD the
     * password is "password" on local and testing machines, and a random one (printed once) everywhere else.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $password = config('admin.seed.password') ?: (app()->environment('local', 'testing') ? 'password' : Str::password(20));

        User::query()->create([
            'name' => 'Saidur Rahman',
            'email' => config('admin.seed.email'),
            'password' => $password,
        ]);

        if (! config('admin.seed.password') && ! app()->environment('local', 'testing')) {
            $this->command?->warn("Admin password (shown once): {$password}");
        }
    }
}
