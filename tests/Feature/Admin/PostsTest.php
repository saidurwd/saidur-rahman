<?php

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    signInAsAdmin();
});

function validPost(array $overrides = []): array
{
    return array_merge([
        'title' => 'Leading ERP rollouts',
        'status' => 'draft',
        'excerpt' => 'What I learned.',
        'body' => '<h2>Context</h2><p>'.str_repeat('word ', 450).'</p>',
    ], $overrides);
}

it('keeps guests out', function () {
    auth()->logout();
    $post = Post::factory()->create();

    foreach ([['get', '/admin/posts'], ['get', '/admin/posts/create'], ['post', '/admin/posts'], ['get', "/admin/posts/{$post->id}/edit"], ['put', "/admin/posts/{$post->id}"], ['delete', "/admin/posts/{$post->id}"], ['get', '/admin/posts/trash'], ['post', '/admin/posts/bulk']] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

describe('list', function () {
    it('lists newest first with status and views', function () {
        Post::factory()->create(['title' => 'Older', 'published_at' => now()->subDays(5), 'views' => 1200]);
        Post::factory()->create(['title' => 'Newer', 'published_at' => now()->subDay()]);

        $this->get('/admin/posts')->assertOk()->assertSeeInOrder(['Newer', 'Older'])->assertSee('1,200')->assertSee('Published');
    });

    it('searches, filters by status and category, and paginates', function () {
        $leadership = Category::factory()->create(['name' => 'Leadership']);
        Post::factory()->create(['title' => 'Alpha ERP', 'category_id' => $leadership->id]);
        Post::factory()->create(['title' => 'Beta HMS', 'status' => PostStatus::Draft, 'published_at' => null]);

        $this->get('/admin/posts?q=alpha')->assertSee('Alpha ERP')->assertDontSee('Beta HMS');
        $this->get('/admin/posts?status=draft')->assertSee('Beta HMS')->assertDontSee('Alpha ERP');
        $this->get("/admin/posts?category={$leadership->id}")->assertSee('Alpha ERP')->assertDontSee('Beta HMS');

        Post::factory()->count(16)->create();
        $this->get('/admin/posts?page=2')->assertOk();
    });
});

describe('create and update', function () {
    it('renders the form with the editor, categories and known tags', function () {
        Category::factory()->create(['name' => 'Leadership']);
        Tag::factory()->create(['name' => 'ERP']);

        $this->get('/admin/posts/create')->assertOk()->assertSee('data-editor="rich"', false)->assertSee('Leadership')->assertSee('ERP');
    });

    it('creates a draft with a generated slug, reading time, category and tags', function () {
        $category = Category::factory()->create();

        $this->post('/admin/posts', validPost(['category_id' => $category->id, 'tags' => 'ERP, Laravel, erp']))
            ->assertRedirect()->assertSessionHas('status');

        $post = Post::firstOrFail();
        expect($post->slug)->toBe('leading-erp-rollouts')
            ->and($post->status)->toBe(PostStatus::Draft)
            ->and($post->reading_minutes)->toBe(3)
            ->and($post->category_id)->toBe($category->id)
            ->and($post->tags->pluck('name')->sort()->values()->all())->toBe(['ERP', 'Laravel']);
    });

    it('reuses existing tags instead of duplicating them', function () {
        Tag::factory()->create(['name' => 'ERP', 'slug' => 'erp']);

        $this->post('/admin/posts', validPost(['tags' => 'ERP']));

        expect(Tag::count())->toBe(1);
    });

    it('validates input', function () {
        $this->post('/admin/posts', ['title' => '', 'status' => 'nope', 'slug' => 'Bad Slug', 'category_id' => 999, 'cover' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['title', 'status', 'slug', 'category_id', 'cover']);
        expect(Post::count())->toBe(0);
    });

    it('purifies the body so scripts never reach the database', function () {
        $this->post('/admin/posts', validPost(['body' => '<p onclick="x()">Hi</p><script>alert(1)</script><a href="javascript:evil()">bad</a>']));

        expect(Post::first()->body)->toContain('Hi')->not->toContain('script')->not->toContain('onclick')->not->toContain('javascript:');
    });

    it('stores a cover image with alt text', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $path = tempnam(sys_get_temp_dir(), 'img').'.png';
        imagepng(imagecreatetruecolor(800, 450), $path);

        $this->post('/admin/posts', validPost(['cover' => new UploadedFile($path, 'cover.png', 'image/png', null, true), 'cover_alt' => 'A diagram']));

        $cover = Post::first()->getFirstMedia('cover');
        expect($cover->getCustomProperty('alt'))->toBe('A diagram')->and($cover->getCustomProperty('width'))->toBe(800);
    });

    it('writes a 301 when the slug changes', function () {
        $post = Post::factory()->create(['slug' => 'old-slug']);

        $this->put("/admin/posts/{$post->id}", validPost(['slug' => 'new-slug', 'status' => 'published']))->assertRedirect();

        expect(Redirect::first()->only(['from_path', 'to_path', 'code']))->toBe(['from_path' => '/blog/old-slug', 'to_path' => '/blog/new-slug', 'code' => 301]);
    });

    it('replaces tags on update', function () {
        $post = Post::factory()->create();
        $post->tags()->attach(Tag::factory()->create(['name' => 'Old'])->id);

        $this->put("/admin/posts/{$post->id}", validPost(['status' => 'published', 'tags' => 'New']))->assertRedirect();

        expect($post->fresh()->tags->pluck('name')->all())->toBe(['New']);
    });
});

describe('status and scheduling', function () {
    it('publishes now when no date is given', function () {
        $this->post('/admin/posts', validPost(['status' => 'published']));

        $post = Post::firstOrFail();
        expect($post->status)->toBe(PostStatus::Published)->and($post->published_at->isPast() || $post->published_at->isToday())->toBeTrue();
    });

    it('requires a date for scheduled articles', function () {
        $this->post('/admin/posts', validPost(['status' => 'scheduled']))->assertSessionHasErrors('published_at');
    });

    it('schedules for the future and turns a future "published" into scheduled', function () {
        $when = now()->addDays(3)->format('Y-m-d\TH:i');

        $this->post('/admin/posts', validPost(['status' => 'scheduled', 'published_at' => $when, 'title' => 'One']));
        $this->post('/admin/posts', validPost(['status' => 'published', 'published_at' => $when, 'title' => 'Two']));

        expect(Post::pluck('status')->all())->toBe([PostStatus::Scheduled, PostStatus::Scheduled]);
    });

    it('turns a scheduled article with a past date into published', function () {
        $this->post('/admin/posts', validPost(['status' => 'scheduled', 'published_at' => now()->subHour()->format('Y-m-d\TH:i')]));

        expect(Post::first()->status)->toBe(PostStatus::Published);
    });

    it('keeps the original publish date when an article is edited', function () {
        $post = Post::factory()->create(['published_at' => now()->subDays(10)->startOfMinute()]);

        $this->put("/admin/posts/{$post->id}", validPost(['status' => 'published']))->assertRedirect();

        expect($post->fresh()->published_at->equalTo($post->published_at))->toBeTrue();
    });

    it('bulk publishes and unpublishes', function () {
        $post = Post::factory()->create(['status' => PostStatus::Draft, 'published_at' => null]);

        $this->post('/admin/posts/bulk', ['action' => 'publish', 'ids' => [$post->id]])->assertRedirect();
        expect($post->fresh()->status)->toBe(PostStatus::Published)->and($post->fresh()->published_at)->not->toBeNull();

        $this->post('/admin/posts/bulk', ['action' => 'unpublish', 'ids' => [$post->id]])->assertRedirect();
        expect($post->fresh()->status)->toBe(PostStatus::Draft);
    });
});

describe('trash', function () {
    it('soft deletes, restores and force deletes', function () {
        $post = Post::factory()->create(['title' => 'Temp post']);

        $this->delete("/admin/posts/{$post->id}")->assertRedirect('/admin/posts');
        $this->get('/admin/posts/trash')->assertSee('Temp post');
        $this->post("/admin/posts/{$post->id}/restore")->assertRedirect();
        expect(Post::find($post->id))->not->toBeNull();

        $post->delete();
        $this->delete("/admin/posts/{$post->id}/force")->assertRedirect();
        expect(Post::withTrashed()->find($post->id))->toBeNull();
    });
});

describe('scheduled publishing command', function () {
    it('publishes due articles only', function () {
        $due = Post::factory()->create(['status' => PostStatus::Scheduled, 'published_at' => now()->subMinute()]);
        $later = Post::factory()->create(['status' => PostStatus::Scheduled, 'published_at' => now()->addHour()]);
        $draft = Post::factory()->create(['status' => PostStatus::Draft, 'published_at' => now()->subDay()]);

        $this->artisan('posts:publish-scheduled')->expectsOutputToContain('Published 1 scheduled')->assertSuccessful();

        expect($due->fresh()->status)->toBe(PostStatus::Published)
            ->and($later->fresh()->status)->toBe(PostStatus::Scheduled)
            ->and($draft->fresh()->status)->toBe(PostStatus::Draft);
    });

    it('is registered to run every minute', function () {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'posts:publish-scheduled'));

        expect($event)->not->toBeNull()->and($event->expression)->toBe('* * * * *');
    });

    it('does nothing when nothing is due', function () {
        $this->artisan('posts:publish-scheduled')->expectsOutputToContain('Published 0')->assertSuccessful();
    });
});
