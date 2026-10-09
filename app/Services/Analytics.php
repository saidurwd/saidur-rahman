<?php

namespace App\Services;

use Illuminate\Http\Request;

/** Privacy-first visitor data: no cookies, no raw IPs, nothing that follows a person from one day to the next. */
class Analytics
{
    /** A hash that only identifies "the same visitor today". The date is part of it, so it cannot be linked across days. */
    public function visitorHash(Request $request): string
    {
        return hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString(), (string) config('app.key'));
    }

    /** Country from Cloudflare's header when the site sits behind it; null otherwise. */
    public function country(Request $request): ?string
    {
        $code = strtoupper((string) $request->header('CF-IPCountry'));

        return preg_match('/^[A-Z]{2}$/', $code) === 1 && $code !== 'XX' && $code !== 'T1' ? $code : null;
    }

    /** The referring site's host only, never the full address. Our own host counts as no referrer. */
    public function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return mb_substr(strtolower($host), 0, 190);
    }

    public function device(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk/i', $userAgent) => 'tablet',
            (bool) preg_match('/mobi|iphone|android/i', $userAgent) => 'mobile',
            default => 'desktop',
        };
    }

    /** Visitors who ask not to be tracked (Do Not Track, Global Privacy Control) are not recorded. */
    public function optedOut(Request $request): bool
    {
        return $request->header('DNT') === '1' || $request->header('Sec-GPC') === '1';
    }
}
