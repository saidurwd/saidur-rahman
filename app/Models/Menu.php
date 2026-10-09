<?php

namespace App\Models;

use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Menu extends Model
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<MenuFactory> */
    use HasFactory;
    use IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['label', 'url', 'route', 'location', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @param Builder<static> $query */
    public function scopeLocation(Builder $query, string $location): void
    {
        $query->where('location', $location);
    }

    public function href(): string
    {
        return $this->route ? route($this->route) : (string) $this->url;
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['label', 'url'];
    }
}
