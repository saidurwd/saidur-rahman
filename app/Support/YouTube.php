<?php

namespace App\Support;

/** Reads a YouTube video ID out of whatever link an admin pastes, and builds the addresses the site uses. */
class YouTube
{
    private const ID = '/^[A-Za-z0-9_-]{11}$/';

    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be'];

    /** Accepts watch, short (youtu.be), embed, shorts and live links, or a bare 11-character ID. */
    public static function id(?string $input): ?string
    {
        $input = trim((string) $input);

        if (preg_match(self::ID, $input)) {
            return $input;
        }

        $parts = parse_url(preg_match('#^https?://#i', $input) ? $input : 'https://'.$input);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', (string) ($parts['path'] ?? ''))));

        if ($host === 'youtu.be') {
            $candidate = $segments[0] ?? '';
        } elseif (in_array($segments[0] ?? '', ['embed', 'shorts', 'live', 'v'], true)) {
            $candidate = $segments[1] ?? '';
        } else {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $candidate = is_string($query['v'] ?? null) ? $query['v'] : '';
        }

        return preg_match(self::ID, $candidate) ? $candidate : null;
    }

    public static function watchUrl(string $id): string
    {
        return 'https://www.youtube.com/watch?v='.$id;
    }

    /** Privacy-enhanced embed: YouTube sets no cookies until the visitor plays the video. */
    public static function embedUrl(string $id, bool $autoplay = false): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0'.($autoplay ? '&autoplay=1' : '');
    }

    public static function thumbnail(string $id, string $size = 'hqdefault'): string
    {
        return "https://i.ytimg.com/vi/{$id}/{$size}.jpg";
    }
}
