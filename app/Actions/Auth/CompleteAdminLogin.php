<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class CompleteAdminLogin
{
    public const SESSION_KEY = 'two_factor_verified';

    public function __invoke(Request $request, User $user): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);

        $user->forceFill(['last_login_at' => now()])->save();
    }
}
