<?php

namespace App\Models;

use App\Contracts\HasPublicPath;
use App\Enums\PostStatus;
use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(ContentObserver::class)]
class Post extends Model implements HasMedia, HasPublicPath
{
    use AdminListFilters, HasPublishState, HasSlug, InteractsWithMedia, LogsChanges, Orderable, SoftDeletes;

    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'category_id', 'status', 'published_at', 'reading_minutes',
        'views', 'seo_title', 'seo_description', 'og_image', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'reading_minutes' => 'integer',
            'views' => 'integer',
        ];
    }

    /**
     * Live posts: published, or scheduled and already due.
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [PostStatus::Published, PostStatus::Scheduled])
            ->where('published_at', '<=', now());
    }

    /** @param Builder<static> $query */
    public function scopeLatestPublished(Builder $query): void
    {
        $query->orderByDesc('published_at');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public static function readingMinutesFor(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('cover')->width(480)->format('webp');
        $this->addMediaConversion('large')->performOnCollections('cover')->width(1400)->format('webp');
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['title', 'excerpt'];
    }

    /**
     * Publishing from the bulk action goes live now unless a date was already set.
     *
     * @return array<string, mixed>
     */
    public function publishState(bool $published): array
    {
        return $published
            ? ['status' => PostStatus::Published, 'published_at' => $this->published_at ?? now()]
            : ['status' => PostStatus::Draft];
    }

    /** @param Builder<static> $query */
    public function scopeWithStatus(Builder $query, ?string $status): void
    {
        $query->when(PostStatus::tryFrom((string) $status), fn (Builder $q, PostStatus $s) => $q->where('status', $s));
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
            $query->whereFullText(['title', 'excerpt', 'body'], $term);

            return;
        }

        // "!" is the escape character, so a typed % or _ is matched literally.
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
        $query->where(fn (Builder $w) => $w
            ->whereRaw('title LIKE ? ESCAPE ?', [$like, '!'])
            ->orWhereRaw('excerpt LIKE ? ESCAPE ?', [$like, '!'])
            ->orWhereRaw('body LIKE ? ESCAPE ?', [$like, '!']));
    }

    public function publicPath(?string $slug = null): string
    {
        return '/blog/'.($slug ?? $this->slug);
    }
}
