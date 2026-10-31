<?php

namespace App\Models;

use App\Contracts\HasPublicPath;
use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use App\Support\YouTube;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Video extends Model implements HasPublicPath
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    use HasSlug, IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'youtube_id', 'description', 'topic', 'is_featured', 'is_published', 'published_at',
        'seo_title', 'seo_description', 'sort_order',
    ];

    protected static function booted(): void
    {
        // A published video always has the date it went live, which is also what the public list sorts by.
        static::saving(function (self $video): void {
            if ($video->is_published && $video->published_at === null) {
                $video->published_at = now();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @param Builder<static> $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /** @param Builder<static> $query */
    public function scopeLatestPublished(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Public search. Uses the MySQL full-text index when the term is long enough for it
     * (InnoDB ignores words under 3 characters) and a plain LIKE match otherwise.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMatching(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        if ($query->getModel()->getConnection()->getDriverName() === 'mysql' && mb_strlen($term) >= 3) {
            $query->whereFullText(['title', 'description'], $term);

            return;
        }

        // "!" is the escape character, so a typed % or _ is matched literally.
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
        $query->where(fn (Builder $w) => $w
            ->whereRaw('title LIKE ? ESCAPE ?', [$like, '!'])
            ->orWhereRaw('description LIKE ? ESCAPE ?', [$like, '!'])
            ->orWhereRaw('topic LIKE ? ESCAPE ?', [$like, '!']));
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['title', 'topic'];
    }

    public function watchUrl(): string
    {
        return YouTube::watchUrl($this->youtube_id);
    }

    public function embedUrl(bool $autoplay = false): string
    {
        return YouTube::embedUrl($this->youtube_id, $autoplay);
    }

    public function thumbnailUrl(string $size = 'hqdefault'): string
    {
        return YouTube::thumbnail($this->youtube_id, $size);
    }

    public function publicPath(?string $slug = null): string
    {
        return '/videos/'.($slug ?? $this->slug);
    }
}
