<?php

namespace App\Services;

/** One definition of "this is probably not a person" for view counting and analytics. */
class Bots
{
    private const PATTERN = '/bot|crawl|spider|slurp|preview|headless|monitor|uptime|curl|wget|python|httpclient|facebookexternalhit|lighthouse|pingdom/i';

    public static function isBot(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' || preg_match(self::PATTERN, $userAgent) === 1;
    }
}
