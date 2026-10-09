<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use App\Observers\ContentObserver;
use Database\Factories\CvFileFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class CvFile extends Model
{
    /** @use HasFactory<CvFileFactory> */
    use HasFactory;

    use LogsChanges, SoftDeletes;

    protected $fillable = ['label', 'file_path', 'is_active', 'downloads'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'downloads' => 'integer',
        ];
    }

    /** @param Builder<static> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @return HasMany<CvDownload, $this> */
    public function downloadLog(): HasMany
    {
        return $this->hasMany(CvDownload::class);
    }
}
