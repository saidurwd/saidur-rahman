<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Cloudflare Turnstile check for the contact form. With no keys configured (local work) it lets everything through. */
class Turnstile
{
    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.turnstile.secret_key')) && filled(config('services.turnstile.site_key'));
    }

    public function siteKey(): ?string
    {
        return $this->enabled() ? (string) config('services.turnstile.site_key') : null;
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::ENDPOINT, array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (Throwable) {
            // If Cloudflare is unreachable, fail closed: better to ask someone to retry than to open the form to bots.
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
