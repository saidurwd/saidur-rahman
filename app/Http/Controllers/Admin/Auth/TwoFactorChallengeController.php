<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\CompleteAdminLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\TwoFactorChallengeRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $this->alreadyDone($request) ?? view('admin.auth.two-factor-challenge');
    }

    public function store(
        TwoFactorChallengeRequest $request,
        TwoFactorService $twoFactor,
        CompleteAdminLogin $complete,
    ): RedirectResponse {
        $user = $request->user();

        if ($redirect = $this->alreadyDone($request)) {
            return $redirect;
        }

        $key = "two-factor:{$user->id}|{$request->ip()}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1]),
            ]);
        }

        $valid = $request->filled('code')
            ? $twoFactor->verify((string) $user->two_factor_secret, (string) $request->string('code'))
            : $twoFactor->useRecoveryCode($user, (string) $request->string('recovery_code'));

        if (! $valid) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                $request->filled('code') ? 'code' : 'recovery_code' => 'The code is not valid.',
            ]);
        }

        RateLimiter::clear($key);
        $complete($request, $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    private function alreadyDone(Request $request): ?RedirectResponse
    {
        if (! $request->user()->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        return $request->session()->get(CompleteAdminLogin::SESSION_KEY)
            ? redirect()->route('admin.dashboard')
            : null;
    }
}
