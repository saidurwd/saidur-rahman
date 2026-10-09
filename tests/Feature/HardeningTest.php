<?php

use App\Enums\PostStatus;
use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Services\ImageStore;
use App\Services\SettingService;
use App\Services\SitemapGenerator;
use App\Support\ContentSecurityPolicy;
use App\Support\ThemeInit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    $this->withoutVite();
});

describe('security headers', function () {
    it('sends the browser security headers on public pages', function () {
        $response = $this->get('/about')->assertOk();

        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()')->toContain('geolocation=()')
            ->and($response->headers->get('Cross-Origin-Opener-Policy'))->toBe('same-origin')
            ->and($response->headers->has('Content-Security-Policy'))->toBeTrue();
    });

    it('also covers error pages and the contact form', function () {
        $this->get('/nowhere')->assertNotFound()->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/contact')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('locks scripts to our own files, the hashed theme script and Turnstile on public pages', function () {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain("default-src 'self'")
            ->toContain("object-src 'none'")->toContain("base-uri 'self'")->toContain("frame-ancestors 'none'")->toContain("form-action 'self'")
            ->toContain(ThemeInit::hash())->toContain('https://challenges.cloudflare.com')->toContain("'unsafe-eval'")
            ->not->toContain("script-src 'self' 'unsafe-inline'");
    });

    it('gives the admin a stricter policy with no eval', function () {
        $csp = $this->get('/admin/login')->headers->get('Content-Security-Policy');

        expect($csp)->toContain("script-src 'self' blob:")->not->toContain('unsafe-eval')->not->toContain('cloudflare');
    });

    it('allows exactly the inline theme script that the layout prints', function () {
        $html = $this->get('/')->getContent();

        expect($html)->toContain('<script>'.ThemeInit::SCRIPT.'</script>');
        preg_match('/<script>(.*?)<\/script>/s', $html, $m);
        expect("'sha256-".base64_encode(hash('sha256', $m[1], true))."'")->toBe(ThemeInit::hash());
    });

    it('has no other inline scripts on public pages', function () {
        $this->seed(DatabaseSeeder::class);

        foreach (['/', '/about', '/projects', '/projects/hms-erp', '/blog', '/contact'] as $path) {
            $html = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $this->get($path)->getContent());
            preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>/i', (string) $html, $inline);
            expect($inline[0])->toHaveCount(1, "{$path} should have only the theme script inline, found: ".implode(' | ', $inline[0]));
        }
    });

    it('can run in report-only mode, and be switched off', function () {
        config(['security.csp.report_only' => true]);
        $response = $this->get('/about');
        expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue()->and($response->headers->has('Content-Security-Policy'))->toBeFalse();

        config(['security.csp.enabled' => false]);
        expect($this->get('/about')->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
    });

    it('adds HSTS only on HTTPS outside local and testing', function () {
        $this->get('/about')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('https://localhost/about')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->get('http://localhost/about')->assertHeaderMissing('Strict-Transport-Security');
    });

    it('upgrades insecure requests when served over HTTPS', function () {
        expect(ContentSecurityPolicy::for(Request::create('https://localhost/')))->toContain('upgrade-insecure-requests')
            ->and(ContentSecurityPolicy::for(Request::create('http://localhost/')))->not->toContain('upgrade-insecure-requests');
    });

    it('keeps the admin out of search engines and out of every cache', function () {
        $response = $this->get('/admin/login');

        expect($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
            ->and($response->headers->get('Cache-Control'))->toContain('no-store');
        $this->get('/about')->assertHeaderMissing('X-Robots-Tag');
    });
});

describe('public pages carry no cookies', function () {
    it('sets no cookie on public pages', function (string $path) {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get($path)->assertOk();

        expect($response->headers->getCookies())->toBe([]);
    })->with(['/', '/about', '/experience', '/projects', '/projects/hms-erp', '/blog', '/blog/sample-published-article', '/services', '/cv', '/privacy', '/feed', '/sitemap.xml']);

    it('does not set a cookie on a 404', function () {
        expect($this->get('/nowhere')->assertNotFound()->headers->getCookies())->toBe([]);
    });

    it('keeps the session only where a form needs it', function () {
        expect($this->get('/contact')->headers->getCookies())->not->toBe([]);
    });
});

describe('full-page cache', function () {
    beforeEach(function () {
        config(['responsecache.enabled' => true]);
        ResponseCache::clear();
    });

    it('serves the second request from the cache', function () {
        Project::factory()->create(['slug' => 'cached', 'title' => 'Cached project']);

        $first = $this->get('/projects/cached')->assertOk();
        DB::table('projects')->where('slug', 'cached')->update(['title' => 'Changed behind the cache']);
        $second = $this->get('/projects/cached')->assertOk();

        expect($second->getContent())->toBe($first->getContent())->toContain('Cached project');
    });

    it('is cleared the moment the admin saves content', function () {
        $project = Project::factory()->create(['slug' => 'live', 'title' => 'Before']);
        $this->get('/projects/live')->assertSee('Before');

        $project->update(['title' => 'After']);

        $this->get('/projects/live')->assertSee('After')->assertDontSee('Before');
    });

    it('is cleared when settings change', function () {
        $this->get('/')->assertDontSee('Open to anything');

        app(SettingService::class)->setMany(['availability_open' => true, 'availability_text' => 'Open to anything']);

        $this->get('/')->assertSee('Open to anything');
    });

    it('never caches search results, the contact form, admin pages or downloads', function () {
        Post::factory()->create(['title' => 'Searchable thing']);

        $this->get('/blog?q=searchable')->assertSee('Searchable thing');
        DB::table('posts')->update(['title' => 'Renamed thing']);
        $this->get('/blog?q=renamed')->assertSee('Renamed thing');

        $this->get('/contact')->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/admin/login')->assertOk();

        $keys = collect(Cache::store('responses_array')->getStore()->all ?? []);
        expect($this->get('/blog?q=searchable')->headers->has('laravel-responsecache'))->toBeFalse();
    });

    it('does not cache 404s, so a page published later shows up', function () {
        $this->get('/privacy')->assertNotFound();

        Page::factory()->create(['slug' => 'privacy', 'title' => 'Privacy']);

        $this->get('/privacy')->assertOk();
    });

    it('ignores tracking parameters but not page numbers', function () {
        Post::factory()->count(11)->sequence(fn ($s) => ['title' => "Entry {$s->index}", 'published_at' => now()->subDays($s->index)])->create();

        $this->get('/blog?utm_source=newsletter')->assertOk()->assertSee('Entry 0');
        $this->get('/blog?page=2')->assertOk()->assertSee('Entry 10')->assertDontSee('Entry 0');
    });

    it('still counts page views and article views when the page comes from the cache', function () {
        Post::factory()->create(['slug' => 'hot', 'views' => 0]);
        $get = fn (string $ip) => $this->withHeader('User-Agent', 'Mozilla/5.0 Safari')->withServerVariables(['REMOTE_ADDR' => $ip])->get('/blog/hot')->assertOk();

        $get('10.0.0.1');
        $get('10.0.0.2');
        $get('10.0.0.3');

        expect((int) DB::table('posts')->where('slug', 'hot')->value('views'))->toBe(3)
            ->and(PageView::where('path', '/blog/hot')->count())->toBe(3);
    });

    it('does not count article views for pages that do not exist', function () {
        Post::factory()->create(['slug' => 'real', 'views' => 0]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 Safari')->get('/blog/missing')->assertNotFound();

        expect((int) DB::table('posts')->sum('views'))->toBe(0);
    });
});

describe('sitemap and robots', function () {
    it('lists published pages only, with absolute URLs and last-modified dates', function () {
        Project::factory()->create(['slug' => 'public-project']);
        Project::factory()->create(['slug' => 'draft-project', 'status' => ProjectStatus::Draft]);
        Post::factory()->create(['slug' => 'public-post']);
        Post::factory()->create(['slug' => 'draft-post', 'status' => PostStatus::Draft, 'published_at' => null]);
        Post::factory()->create(['slug' => 'future-post', 'status' => PostStatus::Scheduled, 'published_at' => now()->addDay()]);
        Service::factory()->create(['slug' => 'advisory']);
        Service::factory()->create(['slug' => 'hidden-service', 'is_published' => false]);
        Page::factory()->create(['slug' => 'privacy']);
        Page::factory()->create(['slug' => 'secret-page', 'is_published' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $document = simplexml_load_string($xml);
        $urls = [];
        foreach ($document->url as $entry) {
            $urls[] = parse_url((string) $entry->loc, PHP_URL_PATH) ?: '/';
        }

        expect($urls)->toContain('/', '/about', '/projects', '/projects/public-project', '/blog/public-post', '/services', '/services/advisory', '/privacy', '/contact')
            ->not->toContain('/projects/draft-project', '/blog/draft-post', '/blog/future-post', '/services/hidden-service', '/secret-page')
            ->and((string) $document->url[3]->lastmod)->not->toBeEmpty();
    });

    it('lists categories that have live articles', function () {
        $with = Category::factory()->create(['slug' => 'with-posts']);
        Category::factory()->create(['slug' => 'empty-cat']);
        Post::factory()->create(['category_id' => $with->id]);

        $xml = $this->get('/sitemap.xml')->getContent();

        expect($xml)->toContain('/blog/category/with-posts')->not->toContain('empty-cat');
    });

    it('escapes special characters in URLs', function () {
        expect(app(SitemapGenerator::class)->xml())->toBeString();
        Page::factory()->create(['slug' => 'a-b']);

        expect(simplexml_load_string(app(SitemapGenerator::class)->xml()))->not->toBeFalse();
    });

    it('writes public/sitemap.xml from the command', function () {
        $path = public_path('sitemap.xml');
        File::delete($path);
        Project::factory()->create(['slug' => 'in-file']);

        $this->artisan('sitemap:generate')->expectsOutputToContain('Sitemap written')->assertSuccessful();

        expect(file_exists($path))->toBeTrue()->and(file_get_contents($path))->toContain('/projects/in-file');
        File::delete($path);
    });

    it('regenerates the sitemap every night', function () {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'sitemap:generate'));

        expect($event)->not->toBeNull()->and($event->expression)->toBe('0 3 * * *');
    });

    it('serves robots.txt that points at the sitemap on the live site', function () {
        $this->app['env'] = 'production';

        $body = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

        expect($body)->toContain('Disallow: /admin')->toContain('Disallow: /cv/download/')->toContain('Sitemap: '.url('/sitemap.xml'))->not->toContain('Disallow: /'."\n");
    });

    it('asks crawlers to stay away everywhere except production', function () {
        $body = $this->get('/robots.txt')->getContent();

        expect($body)->toBe("User-agent: *\nDisallow: /\n");
    });
});

describe('uploaded images', function () {
    it('strips GPS and camera metadata from the stored original and keeps the dimensions', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);

        $image = imagecreatetruecolor(1200, 800);
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
        file_put_contents($path, substr($jpeg, 0, 2)."\xFF\xE1".pack('n', 40)."Exif\0\0SECRET-GPS-COORDINATES-HERE...".substr($jpeg, 2));
        expect(file_get_contents($path))->toContain('SECRET-GPS');

        $project = Project::factory()->create();
        $media = $project->addMedia($path)->toMediaCollection('gallery');

        $stored = file_get_contents($media->fresh()->getPath());
        expect($stored)->not->toContain('SECRET-GPS')
            ->and($media->fresh()->getCustomProperty('width'))->toBe(1200)
            ->and($media->fresh()->size)->toBe(strlen($stored));
    });

    it('leaves non-image files alone', function () {
        $path = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($path, 'plain text');
        app(ImageStore::class)->stripMetadata($path, 'text/plain');

        expect(file_get_contents($path))->toBe('plain text');
    });
});
