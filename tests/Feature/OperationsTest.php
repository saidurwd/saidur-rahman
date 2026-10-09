<?php

use App\Jobs\QueueHeartbeat;
use App\Models\CvFile;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use App\Services\LaunchChecks;
use App\Services\SettingService;
use App\Services\SystemStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;

function checkResult(string $name): array
{
    return collect(app(LaunchChecks::class)->run())->flatten(1)->firstWhere('name', $name);
}

describe('heartbeats', function () {
    it('reports silence when nothing has beaten', function () {
        $status = app(SystemStatus::class);

        expect($status->age(SystemStatus::SCHEDULER))->toBeNull()->and($status->isAlive(SystemStatus::SCHEDULER))->toBeFalse()
            ->and($status->problems())->toHaveCount(2);
    });

    it('is alive for three minutes after a scheduler beat and five-and-a-bit for the queue', function () {
        $status = app(SystemStatus::class);
        $status->beat(SystemStatus::SCHEDULER);
        $status->beat(SystemStatus::QUEUE);

        expect($status->problems())->toBe([]);

        $this->travel(4)->minutes();
        expect($status->isAlive(SystemStatus::SCHEDULER))->toBeFalse()->and($status->isAlive(SystemStatus::QUEUE))->toBeTrue();

        $this->travel(12)->minutes();
        expect($status->isAlive(SystemStatus::QUEUE))->toBeFalse()->and($status->problems())->toHaveCount(2);
    });

    it('records a queue beat when the heartbeat job runs', function () {
        app(QueueHeartbeat::class)->handle(app(SystemStatus::class));

        expect(app(SystemStatus::class)->isAlive(SystemStatus::QUEUE))->toBeTrue();
    });

    it('schedules both heartbeats', function () {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->description ?: $e->command)->implode(' | ');

        expect($commands)->toContain('heartbeat.scheduler')->toContain('QueueHeartbeat');
    });

    it('warns on the dashboard in production only', function () {
        signInAsAdmin();

        $this->get('/admin')->assertDontSee('The scheduler is not running');

        $this->app['env'] = 'production';
        $this->get('/admin')->assertSee('The scheduler is not running')->assertSee('The queue worker is not running');

        app(SystemStatus::class)->beat(SystemStatus::SCHEDULER);
        app(SystemStatus::class)->beat(SystemStatus::QUEUE);
        $this->get('/admin')->assertDontSee('The scheduler is not running')->assertDontSee('The queue worker is not running');
    });
});

describe('launch:check', function () {
    it('fails on a development setup and says why', function () {
        $this->artisan('launch:check')->expectsOutputToContain('Environment is production')->expectsOutputToContain('blocking problem')->assertFailed();
    });

    it('checks the environment settings', function () {
        $this->app['env'] = 'production';
        config(['app.debug' => false, 'app.url' => 'https://saidur-rahman.com', 'session.secure' => true]);

        expect(checkResult('Environment is production')['level'])->toBe('ok')
            ->and(checkResult('Debug mode is off')['level'])->toBe('ok')
            ->and(checkResult('APP_URL is the live https address')['level'])->toBe('ok')
            ->and(checkResult('Session cookies are secure-only')['level'])->toBe('ok');

        config(['app.debug' => true, 'app.url' => 'http://localhost']);
        expect(checkResult('Debug mode is off')['level'])->toBe('fail')->and(checkResult('APP_URL is the live https address')['level'])->toBe('fail');
    });

    it('treats the mail and Turnstile setup as blocking, and the timezone as a warning', function () {
        config(['mail.default' => 'log', 'services.turnstile.site_key' => null, 'services.turnstile.secret_key' => null, 'app.timezone' => 'UTC']);

        expect(checkResult('Mail is really sent')['level'])->toBe('fail')
            ->and(checkResult('Cloudflare Turnstile keys are set')['level'])->toBe('fail')
            ->and(checkResult('Timezone is set to yours')['level'])->toBe('warn');

        config(['mail.default' => 'smtp', 'services.turnstile.site_key' => 'a', 'services.turnstile.secret_key' => 'b', 'app.timezone' => 'Asia/Dhaka']);
        expect(checkResult('Mail is really sent')['level'])->toBe('ok')->and(checkResult('Cloudflare Turnstile keys are set')['level'])->toBe('ok')->and(checkResult('Timezone is set to yours')['level'])->toBe('ok');
    });

    it('requires the background processes to be alive', function () {
        expect(checkResult('Scheduler is running')['level'])->toBe('fail')->and(checkResult('Queue worker is running')['level'])->toBe('fail');

        app(SystemStatus::class)->beat(SystemStatus::SCHEDULER);
        app(SystemStatus::class)->beat(SystemStatus::QUEUE);
        expect(checkResult('Scheduler is running')['level'])->toBe('ok')->and(checkResult('Queue worker is running')['level'])->toBe('ok');
    });

    it('wants encrypted off-site backups', function () {
        config(['backup.backup.destination.disks' => ['local'], 'backup.backup.password' => null]);
        expect(checkResult('Backups go off-site')['level'])->toBe('fail')->and(checkResult('Backup archive is encrypted')['level'])->toBe('fail');

        config(['backup.backup.destination.disks' => ['backups'], 'backup.backup.password' => 'secret']);
        expect(checkResult('Backups go off-site')['level'])->toBe('ok')->and(checkResult('Backup archive is encrypted')['level'])->toBe('ok');
    });

    it('warns about the failed jobs and pending migrations', function () {
        expect(checkResult('Migrations are up to date')['level'])->toBe('ok')->and(checkResult('No failed jobs')['level'])->toBe('ok');

        DB::table('failed_jobs')->insert(['uuid' => 'x', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'boom', 'failed_at' => now()]);
        expect(checkResult('No failed jobs')['level'])->toBe('warn');
    });

    it('finds leftover placeholder text across content types', function () {
        Project::factory()->create(['title' => 'Sample project']);
        Post::factory()->create(['body' => '<p>Sample article body</p>']);
        app(SettingService::class)->set('philosophy', 'Sample: technology serves the business.');

        $check = checkResult('No placeholder "Sample" text left');

        expect($check['level'])->toBe('warn')->and($check['detail'])->toContain('Projects (1)')->toContain('Articles (1)')->toContain('Profile settings (1)');
    });

    it('passes the content checks once the site is filled in', function () {
        Storage::fake(CvFile::DISK);
        Project::factory()->count(3)->create();
        Post::factory()->count(2)->create();
        $cv = CvFile::factory()->create(['is_active' => true]);
        Storage::disk(CvFile::DISK)->put($cv->file_path, '%PDF');
        Page::factory()->create(['slug' => 'privacy']);
        User::factory()->create(['two_factor_confirmed_at' => now(), 'two_factor_secret' => 'x']);
        app(SettingService::class)->setMany(['profile_photo' => 'a.webp', 'og_image' => 'b.webp', 'linkedin' => 'https://www.linkedin.com/in/me', 'contact_email' => 'me@saidur-rahman.com', 'stats' => [['value' => '21+', 'label' => 'Years']]]);

        foreach (['At least 3 published case studies', 'At least 2 published articles', 'A CV is available to download', 'Privacy page is published', 'Admin has two-factor authentication', 'Professional portrait uploaded', 'Share image (1200×630) uploaded', 'LinkedIn link is set', 'Contact email is set', 'Numbers strip has figures you can defend', 'No placeholder "Sample" text left'] as $name) {
            expect(checkResult($name)['level'])->toBe('ok', $name);
        }
    });

    it('flags an admin without 2FA and an empty site', function () {
        User::factory()->create();

        expect(checkResult('Admin has two-factor authentication')['level'])->toBe('fail')->and(checkResult('At least 3 published case studies')['level'])->toBe('warn');
    });

    it('exits zero for warnings only, and non-zero in strict mode', function () {
        $this->mock(LaunchChecks::class, fn ($mock) => $mock->shouldReceive('run')->andReturn(['Content' => [
            ['name' => 'Portrait', 'level' => 'warn', 'detail' => 'not uploaded'],
            ['name' => 'Database', 'level' => 'ok', 'detail' => ''],
        ]]));

        $this->artisan('launch:check')->expectsOutputToContain('No blocking problems')->assertSuccessful();
        $this->artisan('launch:check --strict')->assertFailed();
    });

    it('says the site is ready when everything passes', function () {
        $this->mock(LaunchChecks::class, fn ($mock) => $mock->shouldReceive('run')->andReturn(['Content' => [['name' => 'All good', 'level' => 'ok', 'detail' => '']]]));

        $this->artisan('launch:check --strict')->expectsOutputToContain('Ready to launch')->assertSuccessful();
    });
});

describe('deployment configuration', function () {
    it('keeps nightly backups, cleanup and monitoring on the schedule', function () {
        $expression = fn (string $command) => collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, $command))?->expression;

        expect($expression('backup:clean'))->toBe('0 2 * * *')->and($expression('backup:run'))->toBe('15 2 * * *')->and($expression('backup:monitor'))->toBe('0 4 * * *');
    });

    it('backs up the database, uploads, private files and .env, off-site, for 30 days', function () {
        $include = collect(config('backup.backup.source.files.include'))->map(fn ($p) => str_replace(base_path().'/', '', $p))->all();

        expect($include)->toBe(['storage/app/public', 'storage/app/private', '.env'])
            ->and(config('backup.backup.source.databases'))->toBe([config('database.default')])
            ->and(config('backup.backup.destination.disks'))->not->toBeEmpty()
            ->and(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(30)
            ->and(config('backup.backup.verify_backup'))->toBeTrue()
            ->and(config('backup.backup.source.files.relative_path'))->toBe(base_path());
    });

    it('only emails when a backup goes wrong', function () {
        $notifications = config('backup.notifications.notifications');

        expect($notifications[BackupHasFailedNotification::class])->toBe(['mail'])
            ->and($notifications[BackupWasSuccessfulNotification::class])->toBe([]);
    });

    it('defines an off-site backup disk that works with S3-compatible stores', function () {
        $disk = config('filesystems.disks.backups');

        expect($disk['driver'])->toBe('s3')->and($disk)->toHaveKeys(['key', 'secret', 'bucket', 'endpoint'])->and($disk['throw'])->toBeTrue();
    });

    it('reads the trusted proxies from config so they survive config caching', function () {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9', 'HTTP_X_FORWARDED_PROTO' => 'https']);

        config(['trustedproxy.proxies' => []]);
        (new TrustProxies)->handle($request, fn () => response('ok'));
        expect($request->ip())->toBe('10.0.0.1')->and($request->isSecure())->toBeFalse();

        $trusted = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9', 'HTTP_X_FORWARDED_PROTO' => 'https']);
        config(['trustedproxy.proxies' => ['10.0.0.1']]);
        (new TrustProxies)->handle($trusted, fn () => response('ok'));
        expect($trusted->ip())->toBe('203.0.113.9')->and($trusted->isSecure())->toBeTrue();
    });

    it('does not read the environment outside config files', function () {
        $offenders = collect(['app', 'routes', 'bootstrap/app.php', 'database'])
            ->flatMap(fn ($path) => is_dir(base_path($path)) ? File::allFiles(base_path($path)) : [new SplFileInfo(base_path($path))])
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.php') && preg_match('/\benv\(/', file_get_contents($file->getRealPath())))
            ->map(fn ($file) => str_replace(base_path().'/', '', $file->getRealPath()))
            ->values()->all();

        expect($offenders)->toBe([]);
    });
});
