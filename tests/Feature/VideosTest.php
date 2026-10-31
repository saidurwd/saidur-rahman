<?php

use App\Models\Menu;
use App\Models\Video;
use App\Support\ContentSecurityPolicy;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->withoutVite();
});

function liveVideo(array $attributes = []): Video
{
    return Video::factory()->create($attributes);
}

describe('visibility', function () {
    it('lists only published videos, newest first', function () {
        liveVideo(['title' => 'Older live', 'published_at' => now()->subDays(5)]);
        liveVideo(['title' => 'Newer live', 'published_at' => now()->subDay()]);
        liveVideo(['title' => 'A draft', 'is_published' => false, 'published_at' => null]);
        liveVideo(['title' => 'In the bin'])->delete();

        $this->get('/videos')->assertOk()
            ->assertSeeInOrder(['Newer live', 'Older live'])
            ->assertDontSee('A draft')->assertDontSee('In the bin');
    });

    it('shows a friendly message when there are no videos', function () {
        $this->get('/videos')->assertOk()->assertSee('Videos are coming soon');
    });

    it('returns 404 for drafts, trashed and unknown videos', function () {
        liveVideo(['slug' => 'draft', 'is_published' => false]);
        liveVideo(['slug' => 'trashed'])->delete();

        foreach (['draft', 'trashed', 'nope'] as $slug) {
            $this->get("/videos/{$slug}")->assertNotFound();
        }
    });

    it('appears in the cached list as soon as the admin publishes', function () {
        $video = liveVideo(['title' => 'Coming up', 'is_published' => false]);
        $this->get('/videos')->assertDontSee('Coming up');

        $video->update(['is_published' => true]);

        $this->get('/videos')->assertSee('Coming up');
    });
});

describe('watching', function () {
    it('shows a click-to-play player that loads nothing from YouTube until played', function () {
        liveVideo(['slug' => 'intro', 'title' => 'Intro talk', 'youtube_id' => 'dQw4w9WgXcQ', 'description' => "First line\nSecond <b>line</b>"]);

        $html = $this->get('/videos/intro')->assertOk()->assertSee('Intro talk')->getContent();

        expect($html)->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->and($html)->toContain('x-if="playing"')
            ->and($html)->toContain('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->and($html)->toContain('First line<br />')
            ->and($html)->not->toContain('<b>line</b>');
    });

    it('adds VideoObject and breadcrumb markup', function () {
        liveVideo(['slug' => 'intro', 'title' => 'Intro talk']);

        $this->get('/videos/intro')->assertSee('VideoObject', false)->assertSee('BreadcrumbList', false);
    });

    it('suggests other videos, same topic first', function () {
        liveVideo(['slug' => 'main', 'title' => 'Main one', 'topic' => 'ERP']);
        liveVideo(['title' => 'Different topic', 'topic' => 'Other', 'published_at' => now()->subHours(2)]);
        liveVideo(['title' => 'Same topic', 'topic' => 'ERP', 'published_at' => now()->subDays(9)]);

        $this->get('/videos/main')->assertSeeInOrder(['More to watch', 'Same topic', 'Different topic']);
    });

    it('allows the privacy-enhanced YouTube frame on public pages only', function () {
        $public = ContentSecurityPolicy::for(Request::create('/videos/x'));
        $admin = ContentSecurityPolicy::for(Request::create('/admin/videos'));

        expect($public)->toContain('frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com')
            ->and($admin)->not->toContain('youtube');
    });
});

describe('search and topics', function () {
    it('finds videos by title, description or topic and escapes the search term', function () {
        liveVideo(['title' => 'Laravel queues', 'description' => 'Background jobs', 'topic' => 'Backend']);
        liveVideo(['title' => 'Budgeting', 'description' => 'Plan 100% of spend', 'topic' => 'Finance']);

        $this->get('/videos?q=queues')->assertSee('Laravel queues')->assertDontSee('Budgeting');
        $this->get('/videos?q=jobs')->assertSee('Laravel queues');
        $this->get('/videos?q=Finance')->assertSee('Budgeting')->assertDontSee('Laravel queues');
        $this->get('/videos?q=%25')->assertSee('Budgeting')->assertDontSee('Laravel queues');
    });

    it('never searches unpublished videos and keeps search results out of search engines', function () {
        liveVideo(['title' => 'Secret plans', 'is_published' => false]);

        $this->get('/videos?q=Secret')->assertOk()->assertDontSee('Secret plans')->assertSee('Nothing matched')->assertSee('noindex', false);
    });

    it('filters by topic with counts, ignoring case, and ignores unknown topics', function () {
        liveVideo(['title' => 'About ERP', 'topic' => 'ERP']);
        liveVideo(['title' => 'About Laravel', 'topic' => 'Laravel']);

        $this->get('/videos')->assertSee('ERP (1)', false)->assertSee('Laravel (1)', false);
        $this->get('/videos?topic=erp')->assertSee('About ERP')->assertDontSee('About Laravel');
        $this->get('/videos?topic=unknown')->assertOk()->assertSee('About ERP')->assertSee('About Laravel');
    });

    it('puts featured videos first on the unfiltered first page and paginates the rest', function () {
        liveVideo(['title' => 'Star video', 'is_featured' => true, 'published_at' => now()->subYear()]);
        Video::factory()->count(13)->sequence(fn ($s) => ['published_at' => now()->subHours($s->index + 1)])->create();

        $this->get('/videos')->assertSee('Featured')->assertSee('Star video');
        $this->get('/videos?page=2')->assertOk()->assertDontSee('Featured');
    });
});

describe('site integration', function () {
    it('has a Videos link in the top navigation once added in Menus', function () {
        Menu::factory()->create(['label' => 'Videos', 'url' => '/videos', 'location' => 'header']);

        $this->get('/videos')->assertSee('href="'.url('/videos').'"', false);
    });

    it('lists the videos page and each published video in the sitemap', function () {
        liveVideo(['slug' => 'in-map']);
        liveVideo(['slug' => 'not-in-map', 'is_published' => false]);

        $xml = $this->get('/sitemap.xml')->getContent();

        expect($xml)->toContain(url('/videos'))->toContain(url('/videos/in-map'))->not->toContain('not-in-map');
    });
});
