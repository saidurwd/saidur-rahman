<?php

use App\Enums\PostStatus;
use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\User;
use App\Services\Bots;
use App\Services\CacheService;
use App\Services\HtmlSanitizer;
use App\Services\ImageStore;
use App\Services\RedirectService;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Services\SiteContent;
use App\Services\SiteShell;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use Spatie\ResponseCache\Facades\ResponseCache;

describe('HtmlSanitizer', function () {
    it('keeps safe formatting, links, lists, tables and figures', function () {
        $html = app(HtmlSanitizer::class)->clean('<h2>Title</h2><p>Hi <strong>there</strong> <a href="https://example.com">link</a></p><ul><li>One</li></ul><table><tr><td>Cell</td></tr></table><figure><img src="https://example.com/a.png" alt="A"><figcaption>Caption</figcaption></figure>');

        expect($html)->toContain('<h2>Title</h2>')->toContain('<strong>there</strong>')->toContain('href="https://example.com"')
            ->toContain('<li>One</li>')->toContain('<td>Cell</td>')->toContain('<figure>')->toContain('<figcaption>Caption</figcaption>')->toContain('alt="A"');
    });

    it('removes scripts, event handlers, dangerous URLs, styles, iframes and forms', function () {
        $html = app(HtmlSanitizer::class)->clean('<p style="color:red" onclick="x()">Text</p><script>alert(1)</script><iframe src="https://evil.example"></iframe><form action="/x"><input></form><a href="javascript:evil()">bad</a><img src="data:text/html;base64,AAAA"><a href="https://ok.example">ok</a>');

        expect($html)->toContain('Text')->toContain('https://ok.example')
            ->not->toContain('script')->not->toContain('onclick')->not->toContain('style=')->not->toContain('iframe')->not->toContain('<form')->not->toContain('javascript:')->not->toContain('data:text');
    });

    it('opens links in a new tab without leaking the opener', function () {
        $html = app(HtmlSanitizer::class)->clean('<a href="https://example.com">x</a>');

        expect($html)->toContain('target="_blank"')->toContain('noopener')->toContain('noreferrer')->toContain('nofollow');
    });

    it('turns empty or whitespace-only input into null', function () {
        $sanitizer = app(HtmlSanitizer::class);

        expect($sanitizer->clean(null))->toBeNull()->and($sanitizer->clean("  \n "))->toBeNull()->and($sanitizer->clean('<p>&nbsp;</p>'))->toBeNull();
    });

    it('strips every tag from plain text fields', function () {
        $sanitizer = app(HtmlSanitizer::class);

        expect($sanitizer->plain('<b>Bold</b> text<script>x</script>'))->toBe('Bold textx')->and($sanitizer->plain(' '))->toBeNull()->and($sanitizer->plain(null))->toBeNull();
    });
});

describe('RedirectService', function () {
    it('records a redirect and resolves it by path', function () {
        $service = app(RedirectService::class);
        $service->record('/old', '/new');

        expect($service->resolve('/old')->to_path)->toBe('/new')->and($service->resolve('old/')->to_path)->toBe('/new')->and($service->resolve('/missing'))->toBeNull();
    });

    it('re-points older redirects so visitors never follow a chain', function () {
        $service = app(RedirectService::class);
        $service->record('/a', '/b');
        $service->record('/b', '/c');

        expect(Redirect::orderBy('from_path')->pluck('to_path', 'from_path')->all())->toBe(['/a' => '/c', '/b' => '/c']);
    });

    it('drops a redirect that would point back at the page it now serves', function () {
        $service = app(RedirectService::class);
        $service->record('/a', '/b');
        $service->record('/b', '/a');

        expect(Redirect::pluck('to_path', 'from_path')->all())->toBe(['/b' => '/a']);
    });

    it('ignores a redirect to itself and updates an existing source', function () {
        $service = app(RedirectService::class);
        $service->record('/same', '/same');
        expect(Redirect::count())->toBe(0);

        $service->record('/x', '/y', 302);
        $service->record('/x', '/z');
        expect(Redirect::count())->toBe(1)->and(Redirect::first()->to_path)->toBe('/z');
    });
});

describe('CacheService', function () {
    it('serves cached values until the group is flushed', function () {
        $cache = app(CacheService::class);
        $calls = 0;
        $read = function () use ($cache, &$calls) {
            return $cache->remember('group', 'key', null, function () use (&$calls) {
                return ++$calls;
            });
        };

        expect($read())->toBe(1)->and($read())->toBe(1);
        $cache->flush('group');
        expect($read())->toBe(2);
    });

    it('keeps groups apart', function () {
        $cache = app(CacheService::class);
        $cache->remember('a', 'k', null, fn () => 'a-value');
        $cache->flush('b');

        expect($cache->remember('a', 'k', null, fn () => 'changed'))->toBe('a-value');
    });

    it('supports a time limit', function () {
        $cache = app(CacheService::class);
        $calls = 0;
        $read = function () use ($cache, &$calls) {
            return $cache->remember('ttl', 'k', now()->addMinute(), function () use (&$calls) {
                return ++$calls;
            });
        };

        $read();
        $this->travel(2)->minutes();
        expect($read())->toBe(2);
    });

    it('clears the full-page cache along with the content cache', function () {
        config(['responsecache.enabled' => true]);
        ResponseCache::shouldReceive('clear')->once();

        app(CacheService::class)->flush();
    });
});

describe('TwoFactorService', function () {
    it('generates secrets that verify within a one-step window', function () {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        expect(strlen($secret))->toBe(32)->and($service->verify($secret, $code))->toBeTrue()->and($service->verify($secret, chunk_split($code, 3, ' ')))->toBeTrue()
            ->and($service->verify($secret, '000000'))->toBeFalse()->and($service->verify($secret, '12345'))->toBeFalse()->and($service->verify($secret, 'abcdef'))->toBeFalse();
    });

    it('draws a QR code locally as inline SVG', function () {
        $user = User::factory()->make(['email' => 'me@example.com']);
        $svg = app(TwoFactorService::class)->qrSvg($user, 'JBSWY3DPEHPK3PXP');

        expect($svg)->toStartWith('<svg')->not->toContain('<?xml')->not->toContain('<image')->not->toContain('href=');
        expect(app(TwoFactorService::class)->provisioningUri($user, 'JBSWY3DPEHPK3PXP'))->toStartWith('otpauth://totp/')->toContain('secret=JBSWY3DPEHPK3PXP')->toContain('me%40example.com');
    });

    it('makes eight distinct recovery codes and accepts each only once', function () {
        $service = app(TwoFactorService::class);
        $codes = $service->generateRecoveryCodes();
        $user = User::factory()->create(['two_factor_recovery_codes' => json_encode($codes)]);

        expect($codes)->toHaveCount(8)->and(array_unique($codes))->toHaveCount(8)->and($codes[0])->toMatch('/^[a-z0-9]{5}-[a-z0-9]{5}$/');
        expect($service->useRecoveryCode($user, strtoupper($codes[0])))->toBeTrue()
            ->and($service->useRecoveryCode($user->fresh(), $codes[0]))->toBeFalse()
            ->and($service->useRecoveryCode($user->fresh(), 'nope'))->toBeFalse()
            ->and($user->fresh()->recoveryCodes())->toHaveCount(7);
    });
});

describe('SeoService', function () {
    it('omits empty fields from the Person description', function () {
        app(SettingService::class)->setMany(['site_name' => 'Saidur Rahman', 'headline' => null, 'linkedin' => null, 'github' => null, 'contact_email' => null, 'location' => null]);

        $person = app(SeoService::class)->person();

        expect($person)->toMatchArray(['@type' => 'Person', 'name' => 'Saidur Rahman'])->not->toHaveKeys(['jobTitle', 'sameAs', 'address', 'email']);
    });

    it('numbers breadcrumbs from the home page', function () {
        $crumbs = app(SeoService::class)->breadcrumbs([['Projects', 'https://example.com/projects'], ['HMS', null]]);

        expect(collect($crumbs['itemListElement'])->map(fn ($i) => [$i['position'], $i['name'], $i['item'] ?? null])->all())
            ->toBe([[1, 'Home', url('/')], [2, 'Projects', 'https://example.com/projects'], [3, 'HMS', null]]);
    });
});

describe('Bots', function () {
    it('recognises crawlers and tools, and lets browsers through', function (?string $agent, bool $bot) {
        expect(Bots::isBot($agent))->toBe($bot);
    })->with([
        [null, true], ['', true], ['Googlebot/2.1', true], ['Mozilla/5.0 (compatible; bingbot/2.0)', true], ['curl/8.4.0', true], ['python-requests/2.31', true],
        ['Mozilla/5.0 Chrome-Lighthouse', true], ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/605.1.15', false],
        ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148', false],
    ]);
});

describe('SiteContent', function () {
    it('lists each technology once, ignoring case, sorted', function () {
        Project::factory()->create(['tech_stack' => ['Laravel', 'mysql']]);
        Project::factory()->create(['tech_stack' => ['laravel', 'Alpine.js', 'MySQL']]);

        expect(app(SiteContent::class)->techTags())->toBe(['Alpine.js', 'Laravel', 'mysql']);
    });

    it('finds neighbours at the ends of the list', function () {
        [$a, $b, $c] = collect([1, 2, 3])->map(fn ($i) => Project::factory()->create(['sort_order' => $i]))->all();
        $content = app(SiteContent::class);

        expect($content->neighbours($a)['previous'])->toBeNull()
            ->and($content->neighbours($a)['next']->id)->toBe($b->id)
            ->and($content->neighbours($c)['previous']->id)->toBe($b->id)
            ->and($content->neighbours($c)['next'])->toBeNull();
    });

    it('returns no neighbours for an unpublished project', function () {
        $draft = Project::factory()->create(['status' => ProjectStatus::Draft]);

        expect(app(SiteContent::class)->neighbours($draft))->toBe(['previous' => null, 'next' => null]);
    });

    it('orders categories by their sort order and counts live articles only', function () {
        $second = Category::factory()->create(['name' => 'Second', 'sort_order' => 2]);
        $first = Category::factory()->create(['name' => 'First', 'sort_order' => 1]);
        Post::factory()->count(2)->create(['category_id' => $second->id]);
        Post::factory()->create(['category_id' => $first->id]);
        Post::factory()->create(['category_id' => $first->id, 'status' => PostStatus::Draft, 'published_at' => null]);

        expect(collect(app(SiteContent::class)->categories())->map(fn ($c) => [$c['name'], $c['count']])->all())->toBe([['First', 1], ['Second', 2]]);
    });

    it('ranks related articles by shared tags first, then category', function () {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $main = Post::factory()->create(['category_id' => $category->id]);
        $main->tags()->attach($tag->id);
        $sameCategory = Post::factory()->create(['category_id' => $category->id, 'published_at' => now()->subDay()]);
        $sameTag = Post::factory()->create(['published_at' => now()->subDays(5)]);
        $sameTag->tags()->attach($tag->id);
        Post::factory()->create();

        $related = app(SiteContent::class)->related(app(SiteContent::class)->post($main->slug));

        expect($related->pluck('id')->all())->toBe([$sameTag->id, $sameCategory->id]);
    });

    it('caches a missing page as missing', function () {
        expect(app(SiteContent::class)->page('nope'))->toBeNull()->and(app(SiteContent::class)->project('nope'))->toBeNull();
    });
});

describe('SiteShell', function () {
    it('recomputes for each new request so settings changes show up', function () {
        $settings = app(SettingService::class);
        $settings->set('site_name', 'First');
        $shell = app(SiteShell::class);
        expect($shell->data()['siteName'])->toBe('First');

        $settings->set('site_name', 'Second');
        app()->instance('request', Request::create('/other'));

        expect($shell->data()['siteName'])->toBe('Second');
    });
});

describe('ImageStore', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    function pngUpload(int $width = 400, int $height = 300): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img').'.png';
        imagepng(imagecreatetruecolor($width, $height), $path);

        return new UploadedFile($path, 'x.png', 'image/png', null, true);
    }

    it('stores a WebP, caps the width and returns a path', function () {
        $path = app(ImageStore::class)->store(pngUpload(2400, 1200), 'photos', 800);

        expect($path)->toMatch('#^photos/[A-Za-z0-9]{32}\.webp$#');
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect([$width, $height])->toBe([800, 400]);
    });

    it('does not enlarge small images', function () {
        $path = app(ImageStore::class)->store(pngUpload(300, 200), 'photos', 800);

        expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(300);
    });

    it('refuses a file that is not an image', function () {
        $path = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, 'not an image');

        expect(fn () => app(ImageStore::class)->store(new UploadedFile($path, 'x.txt', 'text/plain', null, true), 'photos'))->toThrow(RuntimeException::class);
    });

    it('deletes files and builds URLs, tolerating empty values', function () {
        $store = app(ImageStore::class);
        $path = $store->store(pngUpload(), 'photos');

        expect($store->url($path))->toContain($path)->and($store->url('https://cdn.example/x.png'))->toBe('https://cdn.example/x.png')->and($store->url(null))->toBeNull();

        $store->delete($path);
        $store->delete(null);
        Storage::disk('public')->assertMissing($path);
    });
});
