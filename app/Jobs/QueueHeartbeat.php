<?php

namespace App\Jobs;

use App\Services\SystemStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Runs through the queue every few minutes. When a worker handles it, the queue is known to be alive. */
class QueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(SystemStatus $status): void
    {
        $status->beat(SystemStatus::QUEUE);
    }
}
