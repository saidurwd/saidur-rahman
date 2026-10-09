<?php

use App\Enums\MessageStatus;
use App\Models\CvDownload;
use App\Models\CvFile;
use App\Models\Message;
use App\Models\Page;
use App\Models\PageView;
use App\Models\Project;
use App\Services\Analytics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();
});

function browse(object $test, string $path = '/about', array $headers = [], string $ip = '10.2.2.2'): TestResponse
{
    return $test->withHeaders($headers + ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Safari/605'])->withServerVariables(['REMOTE_ADDR' => $ip])->get($path);
}

describe('recording', function () {
    it('records a public page view with no raw IP, query string or full referrer', function () {
        browse($this, '/about?utm_source=x&secret=1', ['Referer' => 'https://www.google.com/search?q=saidur', 'CF-IPCountry' => 'bd'], '198.51.100.7')->assertOk();

        $view = PageView::firstOrFail();
        expect($view->path)->toBe('/about')
            ->and($view->ip_hash)->toHaveLength(64)->not->toContain('198.51.100.7')
            ->and($view->country)->toBe('BD')
            ->and($view->referrer)->toBe('www.google.com')
            ->and($view->device)->toBe('desktop')
            ->and($view->created_at)->not->toBeNull();
    });

    it('normalises the home path and detects mobile and tablet', function () {
        browse($this, '/', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile Safari']);
        browse($this, '/about', ['User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0) Safari'], '10.3.3.3');

        expect(PageView::orderBy('id')->pluck('path')->all())->toBe(['/', '/about'])
            ->and(PageView::orderBy('id')->pluck('device')->all())->toBe(['mobile', 'tablet']);
    });

    it('does not count a visit from our own site as a referral', function () {
        browse($this, '/about', ['Referer' => url('/')]);

        expect(PageView::first()->referrer)->toBeNull();
    });

    it('tracks case study, article and editable pages too', function () {
        Project::factory()->create(['slug' => 'hms']);
        Page::factory()->create(['slug' => 'privacy']);

        browse($this, '/projects/hms');
        browse($this, '/privacy');

        expect(PageView::pluck('path')->sort()->values()->all())->toBe(['/privacy', '/projects/hms']);
    });
});

describe('what is not recorded', function () {
    it('skips bots and requests with no user agent', function () {
        browse($this, '/about', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);
        browse($this, '/about', ['User-Agent' => 'Lighthouse']);
        $this->withHeader('User-Agent', '')->get('/about');

        expect(PageView::count())->toBe(0);
    });

    it('respects Do Not Track and Global Privacy Control', function () {
        browse($this, '/about', ['DNT' => '1']);
        browse($this, '/about', ['Sec-GPC' => '1']);

        expect(PageView::count())->toBe(0);
    });

    it('skips the admin, errors, forms, the feed and downloads', function () {
        signInAsAdmin();
        browse($this, '/admin');
        browse($this, '/does-not-exist')->assertNotFound();
        $this->post('/contact', []);
        browse($this, '/feed');
        browse($this, '/styleguide');

        expect(PageView::count())->toBe(0);
    });
});

describe('privacy', function () {
    it('gives the same visitor the same hash within a day but not across days', function () {
        $analytics = app(Analytics::class);
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.4.4.4', 'HTTP_USER_AGENT' => 'Mozilla/5.0']);
        $other = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.5.5.5', 'HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $today = $analytics->visitorHash($request);
        expect($analytics->visitorHash($request))->toBe($today)->and($analytics->visitorHash($other))->not->toBe($today);

        $this->travel(1)->day();
        expect($analytics->visitorHash($request))->not->toBe($today);
    });

    it('ignores nonsense country codes', function () {
        $analytics = app(Analytics::class);

        foreach (['XX', 'T1', 'abc', '', '1'] as $code) {
            expect($analytics->country(Request::create('/', 'GET', server: ['HTTP_CF_IPCOUNTRY' => $code])))->toBeNull();
        }
        expect($analytics->country(Request::create('/', 'GET', server: ['HTTP_CF_IPCOUNTRY' => 'qa'])))->toBe('QA');
    });

    it('prunes page views older than 400 days', function () {
        PageView::create(['path' => '/old'])->forceFill(['created_at' => now()->subDays(401)])->save();
        PageView::create(['path' => '/recent'])->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->artisan('model:prune', ['--model' => PageView::class])->assertSuccessful();

        expect(PageView::pluck('path')->all())->toBe(['/recent']);
    });

    it('schedules the pruning daily', function () {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'model:prune'));

        expect($event)->not->toBeNull()->and($event->expression)->toBe('0 0 * * *');
    });
});

describe('the dashboard', function () {
    beforeEach(function () {
        signInAsAdmin();
    });

    it('shows views and visitors for the last 30 days, with zero-filled days', function () {
        foreach ([['/', 'a', 0], ['/', 'b', 0], ['/about', 'a', 0], ['/', 'c', 3]] as [$path, $visitor, $daysAgo]) {
            PageView::create(['path' => $path, 'ip_hash' => $visitor])->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }
        PageView::create(['path' => '/ancient', 'ip_hash' => 'z'])->forceFill(['created_at' => now()->subDays(45)])->save();

        $response = $this->get('/admin')->assertOk();
        $days = $response->viewData('days');

        expect($days)->toHaveCount(30)
            ->and($days->sum('views'))->toBe(4)
            ->and($days->last())->toMatchArray(['views' => 3, 'visitors' => 2])
            ->and($days->firstWhere('date', now()->subDays(1)->toDateString())['views'])->toBe(0)
            ->and($response->viewData('stats')['views'])->toBe(4)
            ->and($response->viewData('stats')['visitors'])->toBe(3);

        $response->assertSee('data-chart=', false)->assertSee('View as a table');
    });

    it('ranks the top pages and referrers', function () {
        foreach (range(1, 3) as $i) {
            PageView::create(['path' => '/popular', 'referrer' => 'news.example']);
        }
        PageView::create(['path' => '/quiet', 'referrer' => 'blog.example']);

        $this->get('/admin')->assertSeeInOrder(['/popular', '/quiet'])->assertSeeInOrder(['news.example', 'blog.example']);
    });

    it('shows CV downloads by version', function () {
        $cv = CvFile::factory()->create(['label' => 'IT Leadership', 'downloads' => 12]);
        CvDownload::create(['cv_file_id' => $cv->id]);

        $response = $this->get('/admin')->assertOk()->assertSee('IT Leadership');

        expect($response->viewData('cvVersions')->first()->recent)->toBe(1)->and($response->viewData('stats')['downloads'])->toBe(1);
    });

    it('counts messages and leaves spam out of the latest list', function () {
        Message::factory()->create(['name' => 'Real Person']);
        Message::factory()->create(['name' => 'Spam Sam', 'status' => MessageStatus::Spam]);

        $this->get('/admin')->assertSee('Real Person')->assertDontSee('Spam Sam')->assertViewHas('stats', fn ($s) => $s['unread'] === 1);
    });

    it('works with no data at all', function () {
        $this->get('/admin')->assertOk()->assertSee('No page views recorded yet.')->assertSee('No CV uploaded yet.');
    });

    it('does not let visiting the dashboard count as a page view', function () {
        $this->get('/admin');

        expect(PageView::count())->toBe(0);
    });
});
