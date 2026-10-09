<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\TwoFactorService;

class EnableTwoFactor
{
    public function __construct(private TwoFactorService $twoFactor) {}

    /** @return list<string> the plain recovery codes, to show once */
    public function __invoke(User $user, string $secret): array
    {
        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode($codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }
}
