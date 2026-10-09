<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ApplyBulkAction
{
    public const ACTIONS = ['publish', 'unpublish', 'delete', 'restore', 'force-delete'];

    /**
     * Runs the action on each record in turn so observers and the activity log fire.
     * publish/unpublish need the model to use HasPublishState; restore needs SoftDeletes.
     *
     * @param  class-string<Model>  $model
     * @param  list<int|string>  $ids
     * @return int number of records changed
     */
    public function __invoke(string $model, string $action, array $ids): int
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException("Unknown bulk action [{$action}].");
        }

        // withoutGlobalScopes() also lifts the soft-delete scope, so trashed records can be restored.
        $records = $model::query()->withoutGlobalScopes()->whereKey($ids)->get();

        foreach ($records as $record) {
            match ($action) {
                'publish' => $this->setPublished($record, true),
                'unpublish' => $this->setPublished($record, false),
                'delete' => $record->delete(),
                'restore' => method_exists($record, 'restore') ? $record->restore() : null,
                default => $record->forceDelete(), // 'force-delete', the only action left after the guard above
            };
        }

        return $records->count();
    }

    private function setPublished(Model $record, bool $published): void
    {
        if (! method_exists($record, 'publishState')) {
            throw new InvalidArgumentException($record::class.' cannot be published or unpublished.');
        }

        $record->update($record->publishState($published));
    }
}
