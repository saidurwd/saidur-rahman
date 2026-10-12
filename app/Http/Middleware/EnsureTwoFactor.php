<?php

namespace App\Http\Middleware;

use App\Actions\Auth\CompleteAdminLogin;
use App\Actions\Auth\EnableTwoFactor;
use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the admin area. Requires an authenticated user who has set up TOTP and
 * passed the challenge this session. With the `allow-setup` argument, users who have
 * not yet enabled 2FA may pass (that is how they reach the setup page).
 */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next, string $mode = 'strict'): Response
    {
        $user = $request->user();
        $verified = (bool) $request->session()->get(CompleteAdminLogin::SESSION_KEY);

        if (! $user?->hasTwoFactorEnabled()) {
            // Two-factor is required until the admin has deliberately turned it off from the Security page.
            $required = (bool) app(SettingService::class)->get(EnableTwoFactor::REQUIRED_KEY, true);

            return $mode === 'allow-setup' || ! $required ? $next($request) : redirect()->route('admin.two-factor.setup');
        }

        return $verified ? $next($request) : redirect()->route('admin.two-factor.challenge');
    }
}
