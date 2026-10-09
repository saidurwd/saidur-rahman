<?php

namespace App\Http\Middleware;

use App\Actions\Auth\CompleteAdminLogin;
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
            return $mode === 'allow-setup' ? $next($request) : redirect()->route('admin.two-factor.setup');
        }

        return $verified ? $next($request) : redirect()->route('admin.two-factor.challenge');
    }
}
