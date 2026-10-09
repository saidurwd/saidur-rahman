<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(ContentObserver::class)]
class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasSlug, InteractsWithMedia, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'client', 'role', 'period', 'summary', 'problem', 'solution', 'architecture',
        'outcomes', 'tech_stack', 'live_url', 'repo_url', 'is_featured', 'status', 'seo_title',
        'seo_description', 'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'is_featured' => 'boolean',
            'status' => ProjectStatus::class,
        ];
    }

    /** @param Builder<static> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProjectStatus::Published);
    }

    /** @param Builder<static> $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('cover', 'gallery')->width(480)->format('webp');
        $this->addMediaConversion('large')->performOnCollections('cover', 'gallery')->width(1400)->format('webp');
    }
}
