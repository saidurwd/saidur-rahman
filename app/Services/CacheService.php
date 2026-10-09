<?php

namespace App\Services;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Group-versioned cache that works on any store (no tags needed).
 * Flushing a group bumps its version, so every key built from it becomes stale.
 */
class CacheService
{
    public const CONTENT = 'content';

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $group, string $key, DateTimeInterface|int|null $ttl, Closure $callback): mixed
    {
        $key = "{$group}.v{$this->version($group)}.{$key}";

        return $ttl === null
            ? Cache::rememberForever($key, $callback)
            : Cache::remember($key, $ttl, $callback);
    }

    /** Invalidates the group, and the full-page cache with it, since every public page is built from this content. */
    public function flush(string $group = self::CONTENT): void
    {
        Cache::forever("cache-version.{$group}", $this->version($group) + 1);
        ResponseCache::clear();
    }

    private function version(string $group): int
    {
        return (int) Cache::get("cache-version.{$group}", 1);
    }
}
