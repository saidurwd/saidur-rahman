<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\DisableTwoFactor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\DisableTwoFactorRequest;
use App\Services\TrustedDevices;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorManageController extends Controller
{
    public function disable(DisableTwoFactorRequest $request, TwoFactorService $twoFactor, DisableTwoFactor $disable): RedirectResponse
    {
        $user = $request->user();
        $key = "two-factor-disable:{$user->id}|{$request->ip()}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1])]);
        }

        $valid = $request->filled('code')
            ? $twoFactor->verify((string) $user->two_factor_secret, (string) $request->string('code'))
            : $twoFactor->useRecoveryCode($user, (string) $request->string('recovery_code'));

        if (! $valid) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([$request->filled('code') ? 'code' : 'recovery_code' => 'The code is not valid.']);
        }

        RateLimiter::clear($key);
        $disable($user);
        Cookie::queue(app(TrustedDevices::class)->forgetCookie());

        return redirect()->route('admin.two-factor.setup')->with('warning', 'Two-factor authentication is off. Anyone with your password can now sign in to the admin.');
    }

    public function forgetDevices(TrustedDevices $trusted): RedirectResponse
    {
        $trusted->forgetAll();
        Cookie::queue($trusted->forgetCookie());

        return back()->with('status', 'Every trusted browser was forgotten. You will be asked for a code again.');
    }
}
