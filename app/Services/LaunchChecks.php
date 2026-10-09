<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\CvFile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Message;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * The go-live checklist as code. Each check passes (ok), deserves a look (warn) or blocks launch (fail).
 * Run it on the server with `php artisan launch:check` before pointing the domain at the site, and again after a deploy.
 */
class LaunchChecks
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    /** Words that mark text as seeded placeholder content. */
    private const PLACEHOLDER = 'Sample';

    public function __construct(private SettingService $settings, private SystemStatus $system) {}

    /** @return array<string, list<array{name: string, level: string, detail: string}>> */
    public function run(): array
    {
        return [
            'Environment' => $this->environment(),
            'Services' => $this->services(),
            'Operations' => $this->operations(),
            'Content' => $this->content(),
        ];
    }

    /** @param array<string, list<array{level: string}>> $results */
    public static function count(array $results, string $level): int
    {
        return collect($results)->flatten(1)->where('level', $level)->count();
    }

    /** @return list<array{name: string, level: string, detail: string}> */
    private function environment(): array
    {
        $url = (string) config('app.url');

        return [
            $this->check('Environment is production', app()->environment('production'), 'APP_ENV is "'.config('app.env').'"'),
            $this->check('Debug mode is off', ! config('app.debug'), 'APP_DEBUG=true shows stack traces and secrets to visitors'),
            $this->check('APP_URL is the live https address', str_starts_with($url, 'https://') && ! str_contains($url, 'localhost'), "APP_URL is \"{$url}\""),
            $this->check('Application key is set', filled(config('app.key'))),
            $this->check('Session cookies are secure-only', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true'),
            $this->check('Timezone is set to yours', config('app.timezone') !== 'UTC', 'APP_TIMEZONE is UTC, so scheduled articles go live and dates show in UTC', self::WARN),
            $this->check('Reverse proxies are trusted', filled(config('trustedproxy.proxies')), 'Set TRUSTED_PROXIES, otherwise every visitor looks like the same IP address to rate limits', self::WARN),
            $this->check('Security headers and CSP are on', config('security.csp.enabled') && ! config('security.csp.report_only'), 'CSP is switched off or in report-only mode', self::WARN),
            $this->check('Full-page cache is on', (bool) config('responsecache.enabled'), 'RESPONSE_CACHE_ENABLED is false', self::WARN),
            $this->check('Configuration is cached', app()->configurationIsCached(), 'Run php artisan optimize on deploy', self::WARN),
        ];
    }

    /** @return list<array{name: string, level: string, detail: string}> */
    private function services(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('Database connection works', true);
            $checks[] = $this->check('Migrations are up to date', $this->pendingMigrations() === [], count($this->pendingMigrations()).' pending: run php artisan migrate --force');
        } catch (Throwable $e) {
            $checks[] = $this->check('Database connection works', false, $e->getMessage());
        }

        $mailer = (string) config('mail.default');
        $checks[] = $this->check('Mail is really sent', ! in_array($mailer, ['log', 'array'], true), "MAIL_MAILER is \"{$mailer}\": contact messages would only be written to the log");
        $checks[] = $this->check('Mail sender is your own address', ! str_ends_with((string) config('mail.from.address'), '@example.com'), 'MAIL_FROM_ADDRESS is '.config('mail.from.address'), self::WARN);
        $checks[] = $this->check('Cloudflare Turnstile keys are set', filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key')), 'Without them the contact form has no bot check except the honeypot and rate limit');
        $checks[] = $this->check('Public storage link exists', is_link(public_path('storage')) || is_dir(public_path('storage')), 'Run php artisan storage:link');
        $checks[] = $this->check('Storage is writable', is_writable(storage_path('app')) && is_writable(storage_path('logs')));

        return $checks;
    }

    /** @return list<array{name: string, level: string, detail: string}> */
    private function operations(): array
    {
        $queue = (string) config('queue.default');
        $failed = $this->tableCount('failed_jobs');
        $checks = [
            $this->check('Queue is asynchronous', $queue !== 'sync', 'QUEUE_CONNECTION=sync would make the contact form wait for email to send'),
            $this->check('Scheduler is running', $this->system->isAlive(SystemStatus::SCHEDULER), $this->silence(SystemStatus::SCHEDULER, 'cron: * * * * * php artisan schedule:run'), self::FAIL),
            $this->check('Queue worker is running', $this->system->isAlive(SystemStatus::QUEUE), $this->silence(SystemStatus::QUEUE, 'Supervisor: php artisan queue:work'), self::FAIL),
            $this->check('No failed jobs', $failed === 0, "{$failed} failed job(s): see php artisan queue:failed", self::WARN),
            $this->check('Sitemap has been generated', is_file(public_path(SitemapGenerator::FILE)), 'Run php artisan sitemap:generate (it also runs nightly)', self::WARN),
        ];

        return [...$checks, ...$this->backups()];
    }

    /** @return list<array{name: string, level: string, detail: string}> */
    private function backups(): array
    {
        $disks = (array) config('backup.backup.destination.disks');
        $offsite = collect($disks)->reject(fn ($disk) => $disk === 'local')->isNotEmpty();
        $checks = [
            $this->check('Backups go off-site', $offsite, 'BACKUP_DISKS is "'.implode(',', $disks).'": a backup on the same server is lost with it'),
            $this->check('Backup archive is encrypted', filled(config('backup.backup.password')), 'Set BACKUP_ARCHIVE_PASSWORD'),
        ];

        try {
            $newest = collect(BackupDestinationFactory::createFromArray(Config::fromArray(config('backup'))))
                ->map(fn ($destination) => $destination->newestBackup()?->date())
                ->filter()
                ->max();

            $checks[] = $this->check('A recent backup exists', $newest !== null && $newest->diffInHours(now()) < 48, $newest ? 'Newest backup is from '.$newest->toDateTimeString() : 'No backup found yet: run php artisan backup:run', self::WARN);
        } catch (Throwable $e) {
            $checks[] = $this->check('A recent backup exists', false, 'Could not read the backup destination: '.$e->getMessage(), self::WARN);
        }

        return $checks;
    }

    /** @return list<array{name: string, level: string, detail: string}> */
    private function content(): array
    {
        $placeholders = $this->placeholderCounts();
        $admins = User::query()->count();
        $withTwoFactor = User::query()->whereNotNull('two_factor_confirmed_at')->count();
        $setting = fn (string $key) => $this->settings->get($key);

        return [
            $this->check('Admin has two-factor authentication', $admins > 0 && $withTwoFactor === $admins, $admins === 0 ? 'No admin user exists: run php artisan db:seed' : ($admins - $withTwoFactor).' admin(s) have not set up 2FA'),
            $this->check('No placeholder "Sample" text left', $placeholders === [], 'Still in: '.collect($placeholders)->map(fn ($n, $where) => "{$where} ({$n})")->implode(', '), self::WARN),
            $this->check('At least 3 published case studies', Project::published()->count() >= 3, Project::published()->count().' published', self::WARN),
            $this->check('At least 2 published articles', Post::published()->count() >= 2, Post::published()->count().' published', self::WARN),
            $this->check('A CV is available to download', CvFile::published()->get()->contains(fn (CvFile $cv) => Storage::disk(CvFile::DISK)->exists($cv->file_path)), 'No active CV with a file', self::WARN),
            $this->check('Professional portrait uploaded', filled($setting('profile_photo')), 'Profile & settings', self::WARN),
            $this->check('Share image (1200×630) uploaded', filled($setting('og_image')), 'Profile & settings', self::WARN),
            $this->check('LinkedIn link is set', filled($setting('linkedin')), 'Profile & settings', self::WARN),
            $this->check('Contact email is set', filled($setting('contact_email')) && ! str_ends_with((string) $setting('contact_email'), '@example.com'), 'Profile & settings', self::WARN),
            $this->check('Numbers strip has figures you can defend', ! empty($setting('stats')), 'Profile & settings', self::WARN),
            $this->check('Privacy page is published', Page::published()->where('slug', 'privacy')->exists(), 'Pages', self::WARN),
            $this->check('Images have alt text', $this->imagesWithoutAlt() === 0, $this->imagesWithoutAlt().' image(s) without alt text: see Media', self::WARN),
            $this->check('Inbox has no unanswered test messages', Message::unread()->count() === 0, Message::unread()->count().' unread message(s)', self::WARN),
        ];
    }

    /** @return array<string, int> where placeholder text still appears, and how many records */
    private function placeholderCounts(): array
    {
        $like = '%'.self::PLACEHOLDER.'%';
        $tables = [
            'Projects' => [Project::class, ['title', 'summary', 'period', 'problem', 'solution', 'architecture', 'outcomes']],
            'Experience' => [Experience::class, ['company', 'summary']],
            'Education' => [Education::class, ['institution']],
            'Certifications' => [Certification::class, ['name', 'issuer']],
            'Articles' => [Post::class, ['title', 'excerpt', 'body']],
            'Pages' => [Page::class, ['title', 'body']],
            'Services' => [Service::class, ['title', 'summary', 'body']],
            'Testimonials' => [Testimonial::class, ['name', 'quote', 'company']],
        ];

        $found = [];
        foreach ($tables as $label => [$model, $columns]) {
            $count = $model::query()->where(fn ($q) => collect($columns)->each(fn ($column) => $q->orWhere($column, 'like', $like)))->count();
            if ($count > 0) {
                $found[$label] = $count;
            }
        }

        $settingsHit = collect(['bio', 'philosophy', 'positioning'])->filter(fn ($key) => str_contains((string) $this->settings->get($key), self::PLACEHOLDER))->count();
        if ($settingsHit > 0) {
            $found['Profile settings'] = $settingsHit;
        }

        return $found;
    }

    private function imagesWithoutAlt(): int
    {
        return Media::query()->get()
            ->filter(fn ($media) => str_starts_with((string) $media->mime_type, 'image/') && blank($media->getCustomProperty('alt')))
            ->count();
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
        $ran = $migrator->getRepository()->repositoryExists() ? $migrator->getRepository()->getRan() : [];

        return array_values(array_diff(array_keys($files), $ran));
    }

    private function tableCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function silence(string $key, string $hint): string
    {
        $age = $this->system->age($key);

        return ($age === null ? 'No heartbeat yet' : 'Last heartbeat '.$age.' seconds ago').". Expected: {$hint}";
    }

    /**
     * @param  string  $levelIfBad  fail or warn
     * @return array{name: string, level: string, detail: string}
     */
    private function check(string $name, bool $passed, string $detail = '', string $levelIfBad = self::FAIL): array
    {
        return ['name' => $name, 'level' => $passed ? self::OK : $levelIfBad, 'detail' => $passed ? '' : $detail];
    }
}
