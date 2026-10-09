<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Read-only overview of uploaded images. Files are added and removed from the record they belong to. */
class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Media::class);

        $type = $request->string('type')->toString();

        return view('admin.media.index', [
            'media' => Media::query()
                ->with('model')
                ->when($type !== '', fn ($q) => $q->where('model_type', $type))
                ->latest()
                ->paginate(24)
                ->withQueryString(),
            'types' => Media::query()->distinct()->pluck('model_type')->mapWithKeys(fn ($t) => [$t => class_basename($t)])->sort(),
        ]);
    }
}
