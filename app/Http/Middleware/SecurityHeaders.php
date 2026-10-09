<?php

namespace App\Http\Middleware;

use App\Support\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Adds the browser security headers to every web response, cached pages included. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // The Vite dev server runs on another origin, so the policy would block it. It is only for production-like runs.
        if (config('security.csp.enabled') && ! file_exists(public_path('hot'))) {
            $name = config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $headers->set($name, ContentSecurityPolicy::for($request));
        }

        if ($request->isSecure() && config('security.hsts.enabled') && ! app()->environment('local', 'testing')) {
            $headers->set('Strict-Transport-Security', 'max-age='.config('security.hsts.max_age').'; includeSubDomains');
        }

        if ($request->is('admin', 'admin/*')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
