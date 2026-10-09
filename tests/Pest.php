<?php

use App\Actions\Auth\CompleteAdminLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Unit');

/** A signed-in admin who has completed two-factor, for admin feature tests. */
function signInAsAdmin(?TestCase $test = null): User
{
    $user = User::factory()->create([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_recovery_codes' => json_encode([]),
        'two_factor_confirmed_at' => now(),
    ]);

    $test ??= test();
    $test->withoutVite()
        ->actingAs($user)
        ->withSession([CompleteAdminLogin::SESSION_KEY => true]);

    return $user;
}
