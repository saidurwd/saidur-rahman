<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveVideo;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveVideoRequest;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VideoController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Video::class;
    }

    protected function routeBase(): string
    {
        return 'admin.videos';
    }

    protected function moduleLabel(): string
    {
        return 'Videos';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Video::class);

        return view('admin.videos.index', [
            'records' => Video::query()
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->when($request->filled('topic'), fn ($q) => $q->where('topic', $request->string('topic')->toString()))
                ->latestPublished()
                ->latest('created_at')
                ->paginate(15)
                ->withQueryString(),
            'topics' => $this->topics(),
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Video::class);

        return view('admin.videos.form', ['record' => new Video(['is_published' => false]), 'topics' => $this->topics()]);
    }

    public function store(SaveVideoRequest $request, SaveVideo $save): RedirectResponse
    {
        $video = $save(new Video(['sort_order' => Video::nextSortOrder()]), $request->validated());

        return redirect()->route('admin.videos.edit', $video)->with('status', 'Video added.');
    }

    public function edit(Video $video): View
    {
        $this->authorize('update', $video);

        return view('admin.videos.form', ['record' => $video, 'topics' => $this->topics()]);
    }

    public function update(SaveVideoRequest $request, Video $video, SaveVideo $save): RedirectResponse
    {
        $save($video, $request->validated());

        return redirect()->route('admin.videos.edit', $video)->with('status', 'Video saved.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $this->authorize('delete', $video);
        $video->delete();

        return redirect()->route('admin.videos.index')->with('status', 'Video moved to Trash.');
    }

    /** @return Collection<int, string> topics already in use, offered as filters and as suggestions on the form */
    private function topics(): Collection
    {
        return Video::query()->whereNotNull('topic')->distinct()->orderBy('topic')->pluck('topic');
    }
}
