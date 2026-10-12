<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\SettingService;

class DisableTwoFactor
{
    public function __construct(private SettingService $settings) {}

    /** Turns two-factor off for the admin and records that it is no longer required, so the admin can be used without it. */
    public function __invoke(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->settings->set(EnableTwoFactor::REQUIRED_KEY, false, 'security');
    }
}
