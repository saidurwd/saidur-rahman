<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReorderItems
{
    /**
     * Reorders the given ids among themselves. They keep the set of sort_order values they
     * already held, so reordering one page of a paginated list does not disturb the rest.
     *
     * @param  class-string<Model>  $model
     * @param  list<int|string>  $ids  in the new order
     */
    public function __invoke(string $model, array $ids): void
    {
        DB::transaction(function () use ($model, $ids) {
            $slots = $model::query()->whereKey($ids)->orderBy('sort_order')->orderBy('id')->pluck('sort_order')->all();
            $position = 0;

            foreach ($ids as $id) {
                $model::query()->whereKey($id)->update(['sort_order' => $slots[$position] ?? $position]);
                $position++;
            }
        });
    }
}
