<?php

namespace App\Observers;

use App\Services\CacheService;

class ContentObserver
{
    public function __construct(private CacheService $cache) {}

    public function saved(): void
    {
        $this->cache->flush();
    }

    public function deleted(): void
    {
        $this->cache->flush();
    }

    public function restored(): void
    {
        $this->cache->flush();
    }
}
