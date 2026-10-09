<?php

namespace App\Models;

use App\Enums\PostStatus;
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
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasSlug, InteractsWithMedia, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'category_id', 'status', 'published_at', 'reading_minutes',
        'views', 'seo_title', 'seo_description', 'og_image', 'sort_order',
    ];

    /** @return array<string, string> */
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
}
