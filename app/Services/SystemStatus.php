<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Proof that the two background processes the site depends on are alive. The scheduler writes a heartbeat
 * every minute and a queued job writes one every five minutes; if either goes quiet something needs attention.
 */
class SystemStatus
{
    public const SCHEDULER = 'heartbeat.scheduler';

    public const QUEUE = 'heartbeat.queue';

    /** Seconds of silence before a process counts as stopped. */
    private const LIMITS = [self::SCHEDULER => 180, self::QUEUE => 900];

    public function beat(string $key): void
    {
        Cache::forever($key, now()->getTimestamp());
    }

    /** Seconds since the last heartbeat, or null if there has never been one. */
    public function age(string $key): ?int
    {
        $last = Cache::get($key);

        return is_numeric($last) ? max(0, now()->getTimestamp() - (int) $last) : null;
    }

    public function isAlive(string $key): bool
    {
        $age = $this->age($key);

        return $age !== null && $age <= self::LIMITS[$key];
    }

    /** @return list<string> plain-language problems, empty when everything is running */
    public function problems(): array
    {
        $problems = [];

        if (! $this->isAlive(self::SCHEDULER)) {
            $problems[] = 'The scheduler is not running. Scheduled articles, backups and the sitemap will not run. Check the cron entry (php artisan schedule:run every minute).';
        }

        if (! $this->isAlive(self::QUEUE)) {
            $problems[] = 'The queue worker is not running. Contact form emails will not be sent. Check Supervisor (php artisan queue:work).';
        }

        return $problems;
    }
}
