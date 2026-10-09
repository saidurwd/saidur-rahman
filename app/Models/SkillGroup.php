<?php

namespace App\Models;

use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\SkillGroupFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class SkillGroup extends Model
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<SkillGroupFactory> */
    use HasFactory;
    use IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['name', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @return HasMany<Skill, $this> */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->ordered();
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['name'];
    }
}
