<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\SettingService;
use App\Services\TwoFactorService;

class EnableTwoFactor
{
    /** Setting that says the admin must use two-factor. Missing means yes, so a fresh install is protected from the first sign-in. */
    public const REQUIRED_KEY = 'two_factor_required';

    public function __construct(private TwoFactorService $twoFactor, private SettingService $settings) {}

    /** @return list<string> the plain recovery codes, to show once */
    public function __invoke(User $user, string $secret): array
    {
        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode($codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->settings->set(self::REQUIRED_KEY, true, 'security');

        return $codes;
    }
}
