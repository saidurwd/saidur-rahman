<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /** Seeds the single admin. Set ADMIN_EMAIL / ADMIN_PASSWORD in .env, otherwise a password is generated and printed. */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $password = config('admin.seed.password') ?: Str::password(20);

        User::query()->create([
            'name' => 'Saidur Rahman',
            'email' => config('admin.seed.email'),
            'password' => $password,
        ]);

        if (! config('admin.seed.password')) {
            $this->command?->warn("Admin password (shown once): {$password}");
        }
    }
}
