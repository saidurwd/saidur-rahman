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
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Page extends Model implements HasPublicPath
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasSlug, IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['title', 'slug', 'body', 'is_published', 'seo_title', 'seo_description', 'sort_order'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['title', 'slug'];
    }

    public function publicPath(?string $slug = null): string
    {
        return '/'.($slug ?? $this->slug);
    }
}
