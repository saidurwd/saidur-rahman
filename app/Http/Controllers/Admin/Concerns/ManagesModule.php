<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Actions\ApplyBulkAction;
use App\Actions\ReorderItems;
use App\Http\Requests\Admin\BulkActionRequest;
use App\Http\Requests\Admin\ReorderRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The shared half of every admin module: Trash, restore, force delete, drag reorder and bulk actions.
 * A module controller uses this trait, and provides modelClass(), routeBase(), moduleLabel() and labelAttribute().
 * See docs/ADMIN_MODULE_PATTERN.md.
 */
trait ManagesModule
{
    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Route name prefix, e.g. "admin.projects". */
    abstract protected function routeBase(): string;

    /** Plural, human label, e.g. "Projects". */
    abstract protected function moduleLabel(): string;

    /** Attribute shown to name a record in Trash. */
    protected function labelAttribute(): string
    {
        return 'title';
    }

    public function trash(): View
    {
        $this->authorize('viewAny', $this->modelClass());

        return view('admin.modules.trash', [
            'records' => $this->modelClass()::onlyTrashed()->latest('deleted_at')->paginate(15),
            'label' => $this->moduleLabel(),
            'routeBase' => $this->routeBase(),
            'labelAttribute' => $this->labelAttribute(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $record = $this->modelClass()::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $record);
        $record->restore();

        return redirect()->route("{$this->routeBase()}.trash")->with('status', 'Restored.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $record = $this->modelClass()::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $record);
        $record->forceDelete();

        return redirect()->route("{$this->routeBase()}.trash")->with('status', 'Deleted permanently.');
    }

    public function reorder(ReorderRequest $request, ReorderItems $reorder): JsonResponse
    {
        $this->authorize('update', $this->modelClass());
        $reorder($this->modelClass(), $request->validated('ids'));

        return response()->json(['ok' => true]);
    }

    public function bulk(BulkActionRequest $request, ApplyBulkAction $apply): RedirectResponse
    {
        $this->authorize('delete', $this->modelClass());
        $count = $apply($this->modelClass(), $request->validated('action'), $request->validated('ids'));

        return back()->with('status', "{$count} item(s) updated.");
    }
}
