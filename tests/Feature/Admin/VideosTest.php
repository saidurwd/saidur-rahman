<?php

use App\Models\Redirect;
use App\Models\Video;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validVideo(array $overrides = []): array
{
    return array_merge([
        'title' => 'Intro to Laravel',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'is_published' => 1,
    ], $overrides);
}

function makeVideo(array $attributes = []): Video
{
    return Video::factory()->create($attributes);
}

describe('access', function () {
    it('keeps guests out', function () {
        auth()->logout();
        $record = makeVideo();

        foreach ([
            ['get', '/admin/videos'], ['get', '/admin/videos/create'], ['post', '/admin/videos'],
            ['get', "/admin/videos/{$record->id}/edit"], ['put', "/admin/videos/{$record->id}"],
            ['delete', "/admin/videos/{$record->id}"], ['get', '/admin/videos/trash'], ['post', '/admin/videos/bulk'],
        ] as [$method, $url]) {
            $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
        }
    });

    it('appears in the sidebar', function () {
        $this->get('/admin/videos')->assertOk()->assertSee('Videos');
    });
});

describe('index', function () {
    it('lists newest first, searches by title and topic, and paginates', function () {
        makeVideo(['title' => 'Alpha video', 'topic' => 'ERP', 'published_at' => now()->subDays(3)]);
        makeVideo(['title' => 'Beta video', 'topic' => 'Laravel', 'published_at' => now()->subDay()]);

        $this->get('/admin/videos')->assertOk()->assertSeeInOrder(['Beta video', 'Alpha video']);
        $this->get('/admin/videos?q=Alpha')->assertSee('Alpha video')->assertDontSee('Beta video');
        $this->get('/admin/videos?q=Laravel')->assertSee('Beta video')->assertDontSee('Alpha video');

        Video::factory()->count(16)->create();
        $this->get('/admin/videos?page=2')->assertOk();
    });

    it('filters by state and topic', function () {
        makeVideo(['title' => 'Live one', 'topic' => 'ERP']);
        makeVideo(['title' => 'Hidden one', 'topic' => 'Laravel', 'is_published' => false]);

        $this->get('/admin/videos?state=draft')->assertSee('Hidden one')->assertDontSee('Live one');
        $this->get('/admin/videos?state=published')->assertSee('Live one')->assertDontSee('Hidden one');
        $this->get('/admin/videos?topic=ERP')->assertSee('Live one')->assertDontSee('Hidden one');
    });
});

describe('forms', function () {
    it('renders the create and edit forms', function () {
        $record = makeVideo(['youtube_id' => 'dQw4w9WgXcQ']);

        $this->get('/admin/videos/create')->assertOk()->assertSee('YouTube link');
        $this->get("/admin/videos/{$record->id}/edit")->assertOk()->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false);
    });

    it('creates a video from a pasted link and stores only the video id', function () {
        $this->post('/admin/videos', validVideo(['topic' => 'Laravel', 'description' => '<b>Hello</b> world']))
            ->assertRedirect()->assertSessionHas('status');

        $video = Video::firstOrFail();
        expect($video->youtube_id)->toBe('dQw4w9WgXcQ')
            ->and($video->slug)->toBe('intro-to-laravel')
            ->and($video->description)->not->toContain('<b>')
            ->and($video->published_at)->not->toBeNull()
            ->and($video->sort_order)->toBeGreaterThan(0);
    });

    it('accepts the common link shapes', function (string $link) {
        $this->post('/admin/videos', validVideo(['youtube_url' => $link]))->assertSessionHasNoErrors();

        expect(Video::firstOrFail()->youtube_id)->toBe('dQw4w9WgXcQ');
    })->with([
        'youtu.be' => 'https://youtu.be/dQw4w9WgXcQ?t=42',
        'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        'with playlist' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL123',
        'bare id' => 'dQw4w9WgXcQ',
    ]);

    it('rejects links that are not YouTube videos', function (string $link) {
        $this->post('/admin/videos', validVideo(['youtube_url' => $link]))->assertSessionHasErrors('youtube_url');

        expect(Video::count())->toBe(0);
    })->with(['https://vimeo.com/123456789', 'https://evil.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/', 'not a link', 'javascript:alert(1)']);

    it('validates the other fields', function () {
        $this->post('/admin/videos', validVideo(['title' => '', 'slug' => 'Bad Slug', 'topic' => str_repeat('a', 61), 'description' => str_repeat('a', 5001), 'seo_title' => str_repeat('a', 71)]))
            ->assertSessionHasErrors(['title', 'slug', 'topic', 'description', 'seo_title']);

        expect(Video::count())->toBe(0);
    });

    it('shows an error beside the field when a limit is crossed', function () {
        $this->from('/admin/videos/create')->post('/admin/videos', validVideo(['seo_title' => str_repeat('a', 71)]))->assertRedirect('/admin/videos/create');

        $this->get('/admin/videos/create')->assertSee('is-invalid', false)->assertSee('SEO title field must not be greater than 70 characters');
    });

    it('keeps slugs unique', function () {
        makeVideo(['slug' => 'taken']);

        $this->post('/admin/videos', validVideo(['slug' => 'taken']))->assertSessionHasErrors('slug');
    });

    it('updates a video and keeps the old address working when the slug changes', function () {
        $record = makeVideo(['slug' => 'old-slug', 'youtube_id' => 'aaaaaaaaaaa']);

        $this->put("/admin/videos/{$record->id}", validVideo(['slug' => 'new-slug', 'youtube_url' => 'https://youtu.be/bbbbbbbbbbb']))->assertRedirect();

        expect($record->fresh()->slug)->toBe('new-slug')->and($record->fresh()->youtube_id)->toBe('bbbbbbbbbbb')
            ->and(Redirect::where('from_path', '/videos/old-slug')->value('to_path'))->toBe('/videos/new-slug');
    });

    it('keeps the publish date when an unpublished video is later published', function () {
        $this->post('/admin/videos', validVideo(['is_published' => 0]));
        expect(Video::firstOrFail()->published_at)->toBeNull();

        $this->put('/admin/videos/'.Video::first()->id, validVideo());
        expect(Video::first()->published_at)->not->toBeNull();
    });
});

describe('trash and bulk', function () {
    it('soft deletes, shows in trash, restores and force deletes', function () {
        $record = makeVideo(['title' => 'Trash me']);

        $this->delete("/admin/videos/{$record->id}")->assertRedirect('/admin/videos');
        expect(Video::find($record->id))->toBeNull();

        $this->get('/admin/videos/trash')->assertOk()->assertSee('Trash me');
        $this->get('/admin/videos')->assertSee('Trash (1)');

        $this->post("/admin/videos/{$record->id}/restore")->assertRedirect('/admin/videos/trash');
        expect(Video::find($record->id))->not->toBeNull();

        $record->delete();
        $this->delete("/admin/videos/{$record->id}/force")->assertRedirect();
        expect(Video::withTrashed()->find($record->id))->toBeNull();
    });

    it('bulk publishes, unpublishes and trashes', function () {
        $ids = Video::factory()->count(2)->create()->pluck('id')->all();

        $this->post('/admin/videos/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
        expect(Video::published()->count())->toBe(0);

        $this->post('/admin/videos/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
        expect(Video::published()->count())->toBe(2);

        $this->post('/admin/videos/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
        expect(Video::count())->toBe(0)->and(Video::onlyTrashed()->count())->toBe(2);
    });
});

it('records changes in the activity log', function () {
    $record = makeVideo();
    $this->put("/admin/videos/{$record->id}", validVideo(['title' => 'Renamed video']))->assertRedirect();

    expect(Activity::query()->where('subject_type', Video::class)->count())->toBeGreaterThan(0);
});
