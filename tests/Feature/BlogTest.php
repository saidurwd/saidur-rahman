<?php

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Services\ArticleHtml;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->withoutVite();
});

function livePost(array $attributes = []): Post
{
    return Post::factory()->create($attributes);
}

describe('visibility', function () {
    it('lists only live articles, newest first', function () {
        livePost(['title' => 'Older live', 'published_at' => now()->subDays(5)]);
        livePost(['title' => 'Newer live', 'published_at' => now()->subDay()]);
        livePost(['title' => 'A draft', 'status' => PostStatus::Draft, 'published_at' => null]);
        livePost(['title' => 'Future scheduled', 'status' => PostStatus::Scheduled, 'published_at' => now()->addDay()]);
        livePost(['title' => 'Future published', 'published_at' => now()->addDay()]);
        livePost(['title' => 'In the bin'])->delete();

        $this->get('/blog')
            ->assertOk()
            ->assertSeeInOrder(['Newer live', 'Older live'])
            ->assertDontSee('A draft')->assertDontSee('Future scheduled')->assertDontSee('Future published')->assertDontSee('In the bin');
    });

    it('shows a scheduled article once its time has passed, even before the command runs', function () {
        livePost(['title' => 'Due scheduled', 'status' => PostStatus::Scheduled, 'published_at' => now()->subMinute(), 'slug' => 'due']);

        $this->get('/blog')->assertSee('Due scheduled');
        $this->get('/blog/due')->assertOk();
    });

    it('goes live for visitors the moment the scheduled command publishes it', function () {
        $post = livePost(['title' => 'Waiting', 'status' => PostStatus::Scheduled, 'published_at' => now()->addMinutes(10), 'slug' => 'waiting']);
        $this->get('/blog')->assertDontSee('Waiting');
        $this->get('/blog/waiting')->assertNotFound();

        $this->travel(11)->minutes();
        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->get('/blog')->assertSee('Waiting');
        $this->get('/blog/waiting')->assertOk();
        expect($post->fresh()->status)->toBe(PostStatus::Published);
    });

    it('returns 404 for unpublished, trashed and unknown articles', function () {
        livePost(['slug' => 'draft', 'status' => PostStatus::Draft, 'published_at' => null]);
        livePost(['slug' => 'future', 'status' => PostStatus::Scheduled, 'published_at' => now()->addDay()]);
        livePost(['slug' => 'trashed'])->delete();

        foreach (['draft', 'future', 'trashed', 'missing'] as $slug) {
            $this->get("/blog/{$slug}")->assertNotFound();
        }
    });

    it('shows an empty state', function () {
        $this->get('/blog')->assertOk()->assertSee('Articles are coming soon.');
    });

    it('picks up admin edits straight away', function () {
        $post = livePost(['title' => 'Before', 'slug' => 'edit-me']);
        $this->get('/blog')->assertSee('Before');
        $this->get('/blog/edit-me')->assertSee('Before');

        $post->update(['title' => 'After']);

        $this->get('/blog')->assertSee('After')->assertDontSee('Before');
        $this->get('/blog/edit-me')->assertSee('After')->assertDontSee('Before');
    });
});

describe('listing', function () {
    it('paginates nine per page with canonical and prev/next links', function () {
        Post::factory()->count(12)->sequence(fn ($s) => ['title' => 'Post '.str_pad($s->index, 2, '0', STR_PAD_LEFT), 'published_at' => now()->subDays($s->index)])->create();

        $this->get('/blog')->assertSee('Post 00')->assertSee('Post 08')->assertDontSee('Post 09')->assertSee('Page 1 of 2')->assertSee('rel="next"', false);
        $this->get('/blog?page=2')->assertSee('Post 09')->assertSee('Post 11')->assertDontSee('Post 00')
            ->assertSee('rel="prev"', false)->assertSee('rel="canonical" href="'.url('/blog').'?page=2"', false);
    });

    it('filters by category and 404s for unknown or empty ones', function () {
        $leadership = Category::factory()->create(['name' => 'Leadership', 'slug' => 'leadership']);
        $empty = Category::factory()->create(['slug' => 'empty']);
        livePost(['title' => 'Lead post', 'category_id' => $leadership->id]);
        livePost(['title' => 'Other post']);
        livePost(['title' => 'Draft only', 'category_id' => $empty->id, 'status' => PostStatus::Draft, 'published_at' => null]);

        $this->get('/blog/category/leadership')->assertOk()->assertSee('Lead post')->assertDontSee('Other post')->assertSee('Leadership');
        $this->get('/blog/category/empty')->assertNotFound();
        $this->get('/blog/category/nope')->assertNotFound();
    });

    it('shows category chips with counts on the index', function () {
        $category = Category::factory()->create(['name' => 'Engineering', 'slug' => 'engineering']);
        Post::factory()->count(2)->create(['category_id' => $category->id]);

        $this->get('/blog')->assertSee('Engineering (2)')->assertSee('/blog/category/engineering', false);
    });

    it('filters by tag', function () {
        $erp = Tag::factory()->create(['name' => 'ERP', 'slug' => 'erp']);
        livePost(['title' => 'Tagged post'])->tags()->attach($erp->id);
        livePost(['title' => 'Untagged post']);

        $this->get('/blog/tag/erp')->assertOk()->assertSee('Tagged post')->assertDontSee('Untagged post')->assertSee('Tagged: ERP');
        $this->get('/blog/tag/nope')->assertNotFound();
    });
});

describe('search', function () {
    it('matches title, excerpt and body of live articles only', function () {
        livePost(['title' => 'About zebras', 'excerpt' => 'x', 'body' => '<p>nothing</p>']);
        livePost(['title' => 'Plain', 'excerpt' => 'A giraffe story', 'body' => '<p>nothing</p>']);
        livePost(['title' => 'Body match', 'excerpt' => 'x', 'body' => '<p>the okapi lives here</p>']);
        livePost(['title' => 'Draft zebra', 'status' => PostStatus::Draft, 'published_at' => null]);

        $this->get('/blog?q=zebra')->assertSee('About zebras')->assertDontSee('Draft zebra')->assertDontSee('Plain');
        $this->get('/blog?q=giraffe')->assertSee('Plain');
        $this->get('/blog?q=okapi')->assertSee('Body match');
    });

    it('is not indexed, echoes the term safely and explains an empty result', function () {
        $this->get('/blog?q='.urlencode('<script>x</script>'))
            ->assertOk()
            ->assertSee('noindex', false)
            ->assertDontSee('<script>x</script>', false)
            ->assertSee('Nothing matched');
    });

    it('treats LIKE wildcards literally and trims very long terms', function () {
        livePost(['title' => '100% real']);
        livePost(['title' => 'Unrelated']);

        $this->get('/blog?q='.urlencode('%'))->assertSee('100% real')->assertDontSee('Unrelated');
        $this->get('/blog?q='.str_repeat('a', 5000))->assertOk();
    });

    it('keeps the search term across pages', function () {
        Post::factory()->count(11)->sequence(fn ($s) => ['title' => "Needle {$s->index}"])->create();

        $this->get('/blog?q=needle')->assertSee('q=needle&amp;page=2', false);
    });
});

describe('article page', function () {
    it('renders the article with category, tags, reading time and the right metadata', function () {
        $category = Category::factory()->create(['name' => 'Leadership', 'slug' => 'leadership']);
        $post = livePost([
            'title' => 'Running an ERP rollout', 'slug' => 'erp-rollout', 'category_id' => $category->id, 'excerpt' => 'Lessons learned.',
            'body' => '<h2>Plan</h2><p>First.</p><h3>Detail</h3><p>Second.</p><h2>Plan</h2><p>Third.</p>', 'reading_minutes' => 4,
            'published_at' => now()->subDay(),
        ]);
        $post->tags()->attach(Tag::factory()->create(['name' => 'ERP', 'slug' => 'erp'])->id);

        $this->get('/blog/erp-rollout')
            ->assertOk()
            ->assertSee('Running an ERP rollout')->assertSee('Lessons learned.')->assertSee('4 min read')
            ->assertSee('Leadership')->assertSee('/blog/tag/erp', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('article:published_time', false)
            ->assertSee('data-copy-headings', false);
    });

    it('builds a table of contents with unique heading ids', function () {
        livePost(['slug' => 'toc', 'body' => '<h2>Plan</h2><p>a</p><h3>Detail</h3><p>b</p><h2>Plan</h2><p>c</p>']);

        $this->get('/blog/toc')
            ->assertSee('On this page')
            ->assertSee('<h2 id="plan">', false)->assertSee('<h3 id="detail">', false)->assertSee('<h2 id="plan-2">', false)
            ->assertSee('href="#plan-2"', false);
    });

    it('omits the table of contents for short articles', function () {
        livePost(['slug' => 'short', 'body' => '<h2>Only one</h2><p>a</p>']);

        $this->get('/blog/short')->assertDontSee('On this page');
    });

    it('describes the article with Article and breadcrumb JSON-LD', function () {
        app(SettingService::class)->set('site_name', 'Saidur Rahman');
        $post = livePost(['slug' => 'ld', 'title' => 'Structured', 'excerpt' => 'Summary', 'published_at' => now()->subDays(2)]);
        $post->tags()->attach(Tag::factory()->create(['name' => 'ERP'])->id);

        $html = $this->get('/blog/ld')->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $blocks = collect($m[1])->map(fn ($j) => json_decode($j, true));

        $article = $blocks->firstWhere('@type', 'Article');
        expect($article)->toMatchArray(['headline' => 'Structured', 'description' => 'Summary', 'keywords' => 'ERP', 'mainEntityOfPage' => url('/blog/ld')])
            ->and($article['author']['name'])->toBe('Saidur Rahman')
            ->and($article['datePublished'])->toBe($post->published_at->toIso8601String())
            ->and($blocks->pluck('@type')->all())->toContain('BreadcrumbList');
    });

    it('offers share links with the address encoded, and a copy-link button', function () {
        livePost(['slug' => 'share', 'title' => 'Share & care']);

        $this->get('/blog/share')
            ->assertSee('linkedin.com/sharing/share-offsite/?url='.rawurlencode(url('/blog/share')), false)
            ->assertSee('twitter.com/intent/tweet', false)
            ->assertSee('mailto:?subject=', false)
            ->assertSee('Copy link')
            ->assertSee('rel="noopener noreferrer"', false);
    });

    it('shows a reading progress bar', function () {
        livePost(['slug' => 'progress']);

        $this->get('/blog/progress')->assertSee('x-data="readingProgress"', false)->assertSee('id="article"', false);
    });

    it('suggests related articles by shared tags and category, never itself', function () {
        $category = Category::factory()->create();
        $erp = Tag::factory()->create(['name' => 'ERP']);
        $main = livePost(['title' => 'Main article', 'slug' => 'main', 'category_id' => $category->id]);
        $main->tags()->attach($erp->id);
        livePost(['title' => 'Shares tag'])->tags()->attach($erp->id);
        livePost(['title' => 'Shares category', 'category_id' => $category->id]);
        livePost(['title' => 'Unrelated', 'category_id' => Category::factory()->create()->id]);

        $html = $this->get('/blog/main')->assertSee('Related articles')->assertSee('Shares tag')->assertSee('Shares category')->assertDontSee('Unrelated')->getContent();
        expect(substr_count($html, 'Main article'))->toBeGreaterThan(0)
            ->and(substr($html, strpos($html, 'Related articles')))->not->toContain('Main article');
    });

    it('prints the stored HTML without escaping it', function () {
        livePost(['slug' => 'html', 'body' => '<p>Hello <strong>world</strong></p><ul><li>Point</li></ul>']);

        $this->get('/blog/html')->assertSee('<strong>world</strong>', false)->assertSee('<li>Point</li>', false);
    });

    it('follows a slug redirect to the new address', function () {
        livePost(['slug' => 'new-address']);
        Redirect::create(['from_path' => '/blog/old-address', 'to_path' => '/blog/new-address', 'code' => 301]);

        $this->get('/blog/old-address')->assertStatus(301)->assertRedirect('/blog/new-address');
    });
});

describe('view counter', function () {
    function visitPost(object $test, int $postsId, string $agent = 'Mozilla/5.0 (Macintosh) Safari/605', string $ip = '10.0.0.1'): void
    {
        $test->withHeader('User-Agent', $agent)->withServerVariables(['REMOTE_ADDR' => $ip])->get('/blog/counted');
    }

    it('counts one view per visitor per half hour', function () {
        $post = livePost(['slug' => 'counted']);

        visitPost($this, $post->id);
        visitPost($this, $post->id);
        expect($post->fresh()->views)->toBe(1);

        visitPost($this, $post->id, ip: '10.0.0.2');
        expect($post->fresh()->views)->toBe(2);

        $this->travel(31)->minutes();
        visitPost($this, $post->id);
        expect($post->fresh()->views)->toBe(3);
    });

    it('ignores bots and empty user agents', function () {
        $post = livePost(['slug' => 'counted']);

        visitPost($this, $post->id, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        visitPost($this, $post->id, 'curl/8.0');
        $this->withHeader('User-Agent', '')->get('/blog/counted');

        expect($post->fresh()->views)->toBe(0);
    });

    it('does not touch updated_at, the activity log or the cache', function () {
        $post = livePost(['slug' => 'counted', 'updated_at' => now()->subWeek()]);
        $updated = $post->fresh()->updated_at;
        $logs = Activity::count();

        visitPost($this, $post->id);

        expect($post->fresh()->updated_at->equalTo($updated))->toBeTrue()
            ->and(Activity::count())->toBe($logs);
    });

    it('does not count 404s', function () {
        livePost(['slug' => 'draft', 'status' => PostStatus::Draft, 'published_at' => null]);

        $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/blog/draft')->assertNotFound();

        expect((int) DB::table('posts')->sum('views'))->toBe(0);
    });
});

describe('rss feed', function () {
    it('serves valid RSS with live articles only', function () {
        app(SettingService::class)->setMany(['site_name' => 'Saidur Rahman', 'positioning' => 'IT leader']);
        livePost(['title' => 'Tom & Jerry <ERP>', 'slug' => 'tom', 'excerpt' => 'Short & sweet', 'body' => '<p>Full <strong>text</strong> ]]> here</p>']);
        livePost(['title' => 'Hidden draft', 'status' => PostStatus::Draft, 'published_at' => null]);

        $response = $this->get('/feed')->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/rss+xml');

        $xml = simplexml_load_string($response->getContent(), options: LIBXML_NOCDATA);
        expect($xml)->not->toBeFalse()
            ->and((string) $xml->channel->title)->toBe('Saidur Rahman — Articles')
            ->and(count($xml->channel->item))->toBe(1)
            ->and((string) $xml->channel->item[0]->title)->toBe('Tom & Jerry <ERP>')
            ->and((string) $xml->channel->item[0]->link)->toBe(url('/blog/tom'))
            ->and((string) $xml->channel->item[0]->description)->toBe('Short & sweet');
        expect($response->getContent())->not->toContain('Hidden draft');
    });

    it('is advertised on every page and linked from the footer', function () {
        $this->get('/')->assertSee('type="application/rss+xml"', false)->assertSee('RSS feed');
    });

    it('is empty but valid without articles', function () {
        $xml = simplexml_load_string($this->get('/feed')->assertOk()->getContent());

        expect(count($xml->channel->item))->toBe(0);
    });
});

describe('home page', function () {
    it('shows the latest articles and links to them', function () {
        livePost(['title' => 'Home teaser', 'slug' => 'home-teaser']);
        livePost(['title' => 'Draft teaser', 'status' => PostStatus::Draft, 'published_at' => null]);

        $this->get('/')->assertSee('Latest articles')->assertSee('Home teaser')->assertSee('/blog/home-teaser', false)->assertDontSee('Draft teaser');
    });
});

describe('table of contents builder', function () {
    it('adds ids, dedupes and skips empty headings', function () {
        $result = app(ArticleHtml::class)->process('<h2>Intro</h2><h2></h2><h3>Intro</h3><p>é ü text</p>');

        expect($result['toc'])->toBe([['id' => 'intro', 'text' => 'Intro', 'level' => 2], ['id' => 'intro-2', 'text' => 'Intro', 'level' => 3]])
            ->and($result['html'])->toContain('<h2 id="intro">')->toContain('é ü text');
    });

    it('handles headings with no latin letters and empty input', function () {
        $result = app(ArticleHtml::class)->process('<h2>مرحبا</h2>');

        expect($result['toc'][0]['id'])->toBeString()->not->toBeEmpty()
            ->and(app(ArticleHtml::class)->process(null))->toBe(['html' => '', 'toc' => []]);
    });
});
