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
use Illuminate\Database\Eloquent\Collection;

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
