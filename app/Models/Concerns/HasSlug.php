<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills a unique slug on create when none is given. Slugs stay stable after that;
 * changing one is an explicit admin action (which writes a redirect).
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlug((string) $model->{$model->slugSource()});
            }
        });
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $i = 2;

        // withoutGlobalScopes() also lifts the soft-delete scope, so trashed slugs are never reused.
        while (static::query()->withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
