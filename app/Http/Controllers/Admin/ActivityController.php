<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/** Read-only audit trail: who changed what, and when. */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $type = $request->string('type')->toString();
        $event = $request->string('event')->toString();

        return view('admin.activity.index', [
            'activities' => Activity::query()
                ->with(['causer', 'subject'])
                ->when($type !== '', fn ($q) => $q->where('subject_type', $type))
                ->when($event !== '', fn ($q) => $q->where('event', $event))
                ->latest()
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'types' => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type')->mapWithKeys(fn ($t) => [$t => class_basename($t)])->sort(),
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ]);
    }
}
