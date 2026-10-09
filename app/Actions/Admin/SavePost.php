<?php

namespace App\Actions\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\HtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavePost
{
    public function __construct(
        private SaveSlugged $saveSlugged,
        private HtmlSanitizer $sanitizer,
        private CacheService $cache,
    ) {}

    /**
     * Saves an article: settles status and publish date, purifies the body, works out the reading
     * time, keeps the slug stable (301 on change), syncs tags and applies the cover image.
     *
     * @param  array<string, mixed>  $data  validated request data
     */
    public function __invoke(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            [$status, $publishedAt] = $this->settleStatus($post, PostStatus::from($data['status']), $data['published_at'] ?? null);

            $body = $this->sanitizer->clean($data['body'] ?? null);

            $core = Arr::only($data, ['title', 'slug', 'excerpt', 'category_id', 'seo_title', 'seo_description']) + [
                'body' => $body,
                'status' => $status,
                'published_at' => $publishedAt,
                'reading_minutes' => Post::readingMinutesFor($body),
            ];

            ($this->saveSlugged)($post, $core, ['body'], ['excerpt']);

            $post->tags()->sync($this->tagIds($data['tags'] ?? []));
            $this->syncCover($post, $data);
            $this->cache->flush();

            return $post;
        });
    }

    /**
     * A published post with a future date is really scheduled; a scheduled one whose time has
     * already passed is really published. Drafts keep whatever date was typed.
     *
     * @return array{0: PostStatus, 1: ?Carbon}
     */
    private function settleStatus(Post $post, PostStatus $status, mixed $input): array
    {
        $date = filled($input) ? Carbon::parse($input) : null;

        if ($status === PostStatus::Draft) {
            return [$status, $date];
        }

        $date ??= $post->published_at ?? now();

        return [$date->isFuture() ? PostStatus::Scheduled : PostStatus::Published, $date];
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function tagIds(array $names): array
    {
        return collect($names)->map(function (string $name): int {
            $tag = Tag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

            return $tag->id;
        })->unique()->values()->all();
    }

    /** @param array<string, mixed> $data */
    private function syncCover(Post $post, array $data): void
    {
        if (! empty($data['remove_cover'])) {
            $post->clearMediaCollection('cover');
        }

        if (($data['cover'] ?? null) instanceof UploadedFile) {
            $post->addMedia($data['cover'])->withCustomProperties(['alt' => $data['cover_alt'] ?? ''])->toMediaCollection('cover');
        } elseif ($cover = $post->getFirstMedia('cover')) {
            $cover->setCustomProperty('alt', $data['cover_alt'] ?? '')->save();
        }
    }
}
