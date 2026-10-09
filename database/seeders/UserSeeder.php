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

        $password = env('ADMIN_PASSWORD') ?: Str::password(20);

        User::query()->create([
            'name' => 'Saidur Rahman',
            'email' => env('ADMIN_EMAIL', 'saidurwd@gmail.com'),
            'password' => $password,
        ]);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Admin password (shown once): {$password}");
        }
    }
}
