<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Orderable
{
    /** The next free position, counting trashed records so a restored one never collides. */
    public static function nextSortOrder(): int
    {
        return (int) static::query()->withoutGlobalScopes()->max('sort_order') + 1;
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
