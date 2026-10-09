<?php

use App\Jobs\QueueHeartbeat;
use App\Services\SystemStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();

Schedule::command('model:prune')->daily();

Schedule::command('sitemap:generate')->dailyAt('03:00');

// Backups: nightly dump of the database plus uploads and .env, sent off-site and encrypted. Failures are emailed.
Schedule::command('backup:clean')->dailyAt('02:00');
Schedule::command('backup:run')->dailyAt('02:15');
Schedule::command('backup:monitor')->dailyAt('04:00');

// Heartbeats let the admin dashboard and `php artisan launch:check` notice a stopped scheduler or queue worker.
Schedule::call(fn () => app(SystemStatus::class)->beat(SystemStatus::SCHEDULER))->everyMinute()->name('heartbeat.scheduler');
Schedule::job(new QueueHeartbeat)->everyFiveMinutes();
