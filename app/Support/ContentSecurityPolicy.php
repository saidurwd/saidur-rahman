<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Builds the Content-Security-Policy header.
 *
 * Public pages need 'unsafe-eval' because Alpine.js compiles its attribute expressions at runtime
 * (moving to Alpine's CSP build would mean rewriting every inline expression). The admin does not use
 * Alpine, so it gets the stricter policy. Inline styles are allowed because the markup uses a few
 * style attributes; scripts are what matters, and those are limited to our own files plus one hashed inline script.
 */
class ContentSecurityPolicy
{
    private const TURNSTILE = 'https://challenges.cloudflare.com';

    public static function for(Request $request): string
    {
        $admin = $request->is('admin', 'admin/*');

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => $admin
                ? ["'self'", 'blob:']
                : ["'self'", ThemeInit::hash(), "'unsafe-eval'", self::TURNSTILE],
            'style-src' => ["'self'", "'unsafe-inline'"],
            // Articles can embed images by URL, and the editor previews uploads with blob: and data: addresses.
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => $admin ? ["'self'"] : ["'self'", self::TURNSTILE],
            'frame-src' => $admin ? ["'self'", 'blob:'] : [self::TURNSTILE],
            'media-src' => ["'self'"],
            'worker-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        if ($request->isSecure()) {
            $directives['upgrade-insecure-requests'] = [];
        }

        return collect($directives)
            ->map(fn (array $values, string $name) => trim($name.' '.implode(' ', $values)))
            ->implode('; ');
    }
}
