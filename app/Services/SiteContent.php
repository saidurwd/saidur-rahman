<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\CvFile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\SkillGroup;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Everything the public site reads. Always published content only, always cached in the
 * `content` group, which the model observers clear whenever the admin saves something.
 */
class SiteContent
{
    public function __construct(private CacheService $cache) {}

    /** @return Collection<int, Project> */
    public function projects(): Collection
    {
        return $this->remember('projects', fn () => Project::query()->published()->with('media')->ordered()->get());
    }

    /** @return Collection<int, Project> */
    public function featuredProjects(int $limit = 3): Collection
    {
        $featured = $this->projects()->where('is_featured', true)->take($limit);

        return $featured->isNotEmpty() ? $featured->values() : $this->projects()->take($limit)->values();
    }

    public function project(string $slug): ?Project
    {
        return $this->projects()->firstWhere('slug', $slug);
    }

    /** @return array{previous: ?Project, next: ?Project} */
    public function neighbours(Project $project): array
    {
        $all = $this->projects()->values();
        $index = $all->search(fn (Project $p) => $p->is($project));

        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        return ['previous' => $all->get($index - 1), 'next' => $all->get($index + 1)];
    }

    /** @return list<string> distinct technologies across published projects, A to Z */
    public function techTags(): array
    {
        return $this->projects()
            ->flatMap(fn (Project $p) => $p->tech_stack ?? [])
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** @return Collection<int, Experience> */
    public function experiences(): Collection
    {
        return $this->remember('experiences', fn () => Experience::query()->published()->ordered()->get());
    }

    /** @return Collection<int, Education> */
    public function education(): Collection
    {
        return $this->remember('education', fn () => Education::query()->published()->ordered()->get());
    }

    /** @return Collection<int, Certification> */
    public function certifications(): Collection
    {
        return $this->remember('certifications', fn () => Certification::query()->published()->ordered()->get());
    }

    /** @return Collection<int, SkillGroup> groups that have at least one published skill */
    public function skillGroups(): Collection
    {
        return $this->remember('skill-groups', fn () => SkillGroup::query()
            ->published()
            ->with(['skills' => fn ($q) => $q->published()])
            ->ordered()
            ->get()
            ->filter(fn (SkillGroup $group) => $group->skills->isNotEmpty())
            ->values());
    }

    /** @return Collection<int, Service> */
    public function services(): Collection
    {
        return $this->remember('services', fn () => Service::query()->published()->ordered()->get());
    }

    public function service(string $slug): ?Service
    {
        return $this->services()->firstWhere('slug', $slug);
    }

    /** @return Collection<int, Post> */
    public function latestPosts(int $limit = 3): Collection
    {
        return $this->remember("posts.latest.{$limit}", fn () => Post::query()->published()->latestPublished()->limit($limit)->get());
    }

    public const POSTS_PER_PAGE = 9;

    /** Columns for article lists. The body is left out so the cached list stays small. */
    private const POST_LIST = ['id', 'title', 'slug', 'excerpt', 'category_id', 'status', 'published_at', 'reading_minutes', 'views', 'seo_title', 'seo_description', 'created_at', 'updated_at'];

    /** @return Collection<int, Post> all live articles, newest first, without their bodies */
    public function posts(): Collection
    {
        return $this->remember('posts', fn () => Post::query()
            ->published()
            ->select(self::POST_LIST)
            ->with(['category', 'tags', 'media'])
            ->orderByDesc('published_at')
            ->get());
    }

    /** @return list<array{name: string, slug: string, count: int}> categories that have live articles */
    public function categories(): array
    {
        return $this->posts()
            ->filter(fn (Post $post) => $post->category !== null)
            ->groupBy('category_id')
            ->map(fn ($posts) => ['name' => $posts->first()->category->name, 'slug' => $posts->first()->category->slug, 'count' => $posts->count(), 'order' => (int) $posts->first()->category->sort_order])
            ->sortBy('order')
            ->map(fn (array $c) => ['name' => $c['name'], 'slug' => $c['slug'], 'count' => $c['count']])
            ->values()
            ->all();
    }

    /** @return array{name: string, slug: string, count: int}|null */
    public function category(string $slug): ?array
    {
        return collect($this->categories())->firstWhere('slug', $slug);
    }

    public function tag(string $slug): ?Tag
    {
        return $this->posts()->flatMap(fn (Post $post) => $post->tags)->firstWhere('slug', $slug);
    }

    /** @return LengthAwarePaginator<int, Post> */
    public function blogPage(?string $categorySlug, ?string $tagSlug, int $page): LengthAwarePaginator
    {
        $posts = $this->posts()
            ->when($categorySlug, fn ($c) => $c->filter(fn (Post $p) => $p->category?->slug === $categorySlug))
            ->when($tagSlug, fn ($c) => $c->filter(fn (Post $p) => $p->tags->contains('slug', $tagSlug)))
            ->values();

        return new LengthAwarePaginator(
            $posts->forPage($page, self::POSTS_PER_PAGE)->values(),
            $posts->count(),
            self::POSTS_PER_PAGE,
            $page,
            ['path' => url()->current()],
        );
    }

    /**
     * Search is not cached (every query is different), but it still only sees live articles.
     *
     * @return LengthAwarePaginator<int, Post>
     */
    public function searchPosts(string $term, int $page): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->matching($term)
            ->select(self::POST_LIST)
            ->with(['category', 'tags', 'media'])
            ->orderByDesc('published_at')
            ->paginate(self::POSTS_PER_PAGE, page: $page)
            ->appends(['q' => $term]);
    }

    /** A single live article with its body. */
    public function post(string $slug): ?Post
    {
        return $this->remember("post.{$slug}", fn () => Post::query()->published()->with(['category', 'tags', 'media'])->where('slug', $slug)->first());
    }

    /**
     * Other live articles, ranked by shared tags and category, newest first on ties.
     *
     * @return Collection<int, Post>
     */
    public function related(Post $post, int $limit = 3): Collection
    {
        $tagIds = $post->tags->pluck('id');

        return $this->posts()
            ->reject(fn (Post $other) => $other->id === $post->id)
            ->map(function (Post $other) use ($post, $tagIds) {
                $other->setAttribute('relevance', $other->tags->pluck('id')->intersect($tagIds)->count() * 2 + ($other->category_id && $other->category_id === $post->category_id ? 1 : 0));

                return $other;
            })
            ->filter(fn (Post $other) => $other->getAttribute('relevance') > 0)
            ->sortByDesc(fn (Post $other) => [$other->getAttribute('relevance'), $other->published_at])
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, Post> the latest articles with their bodies, for the RSS feed */
    public function feedPosts(int $limit = 20): Collection
    {
        return $this->remember('feed', fn () => Post::query()->published()->with('category')->orderByDesc('published_at')->limit($limit)->get());
    }

    public function page(string $slug): ?Page
    {
        return $this->remember("page.{$slug}", fn () => Page::query()->published()->where('slug', $slug)->first());
    }

    public function hasActiveCv(): bool
    {
        return (bool) $this->remember('cv.active', fn () => CvFile::query()->published()->exists());
    }

    /** Caches a value. A `false` placeholder stands in for null so missing pages are cached too. */
    private function remember(string $key, \Closure $callback): mixed
    {
        $value = $this->cache->remember(CacheService::CONTENT, "site.{$key}", null, fn () => $callback() ?? false);

        return $value === false ? null : $value;
    }
}
