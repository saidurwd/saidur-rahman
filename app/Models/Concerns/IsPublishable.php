<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** For models with an is_published boolean. */
trait IsPublishable
{
    /** @param Builder<static> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
