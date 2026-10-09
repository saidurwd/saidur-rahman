<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Search and published-state filters for admin list screens. Override the two protected methods to change columns. */
trait AdminListFilters
{
    /** @param Builder<static> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        $query->when($term !== '', function (Builder $q) use ($term) {
            $q->where(function (Builder $w) use ($term) {
                foreach ($this->adminSearchColumns() as $column) {
                    $w->orWhere($column, 'like', "%{$term}%");
                }
            });
        });
    }

    /** @param Builder<static> $query */
    public function scopeWithState(Builder $query, ?string $state): void
    {
        if (in_array($state, ['published', 'draft'], true)) {
            $query->where($this->publishColumn(), $state === 'published');
        }
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['title'];
    }

    protected function publishColumn(): string
    {
        return 'is_published';
    }
}
