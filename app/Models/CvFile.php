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
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ContentObserver::class)]
class CvFile extends Model
{
    /** @use HasFactory<CvFileFactory> */
    use HasFactory;

    use LogsChanges, SoftDeletes;

    protected $fillable = ['label', 'file_path', 'is_active', 'downloads'];

    public const DISK = 'local';

    protected static function booted(): void
    {
        static::forceDeleted(fn (self $cv) => Storage::disk(self::DISK)->delete($cv->file_path));
    }

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

    public function sizeForHumans(): string
    {
        $bytes = Storage::disk(self::DISK)->exists($this->file_path) ? Storage::disk(self::DISK)->size($this->file_path) : 0;

        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024).' KB';
    }
}
