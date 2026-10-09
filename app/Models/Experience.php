<?php

namespace App\Models;

use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Observers\ContentObserver;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    use IsPublishable, LogsChanges, SoftDeletes;

    protected $fillable = ['company', 'role', 'location', 'employment_type', 'start_date', 'end_date', 'is_current', 'summary', 'achievements', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'achievements' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('is_current')->orderByDesc('start_date');
    }
}
