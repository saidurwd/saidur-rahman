<?php

namespace App\Models;

use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    use IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['skill_group_id', 'name', 'level', 'years', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'years' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<SkillGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SkillGroup::class, 'skill_group_id');
    }
}
