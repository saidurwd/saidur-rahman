<?php

namespace App\Models;

use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(ContentObserver::class)]
class Testimonial extends Model implements HasMedia
{
    use AdminListFilters, HasPublishState, InteractsWithMedia;

    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    use LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['name', 'position', 'company', 'quote', 'photo', 'is_approved', 'sort_order'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /** @param Builder<static> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['name', 'company', 'quote'];
    }

    protected function publishColumn(): string
    {
        return 'is_approved';
    }

    /** @return array<string, mixed> */
    public function publishState(bool $published): array
    {
        return ['is_approved' => $published];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('photo')->fit(Fit::Crop, 240, 240)->format('webp');
    }
}
