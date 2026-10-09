<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveCvFile;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCvFileRequest;
use App\Models\CvFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvFileController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return CvFile::class;
    }

    protected function routeBase(): string
    {
        return 'admin.cv-files';
    }

    protected function moduleLabel(): string
    {
        return 'CV files';
    }

    protected function labelAttribute(): string
    {
        return 'label';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CvFile::class);
        $term = $request->string('q')->toString();

        return view('admin.cv-files.index', [
            'records' => CvFile::query()
                ->when($term !== '', fn ($q) => $q->where('label', 'like', "%{$term}%"))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CvFile::class);

        return view('admin.cv-files.form', ['record' => new CvFile(['is_active' => true])]);
    }

    public function store(SaveCvFileRequest $request, SaveCvFile $save): RedirectResponse
    {
        $save(new CvFile, $request->safe()->all());

        return redirect()->route('admin.cv-files.index')->with('status', 'CV uploaded.');
    }

    public function edit(CvFile $cv_file): View
    {
        $this->authorize('update', $cv_file);

        return view('admin.cv-files.form', ['record' => $cv_file]);
    }

    public function update(SaveCvFileRequest $request, CvFile $cv_file, SaveCvFile $save): RedirectResponse
    {
        $save($cv_file, $request->safe()->all());

        return redirect()->route('admin.cv-files.index')->with('status', 'CV saved.');
    }

    public function destroy(CvFile $cv_file): RedirectResponse
    {
        $this->authorize('delete', $cv_file);
        $cv_file->delete();

        return redirect()->route('admin.cv-files.index')->with('status', 'CV moved to Trash.');
    }

    public function activate(CvFile $cv_file, SaveCvFile $save): RedirectResponse
    {
        $this->authorize('update', $cv_file);
        $save->activate($cv_file);

        return back()->with('status', "{$cv_file->label} is now the active version.");
    }

    public function download(CvFile $cv_file): StreamedResponse
    {
        $this->authorize('view', $cv_file);
        abort_unless(Storage::disk(CvFile::DISK)->exists($cv_file->file_path), 404);

        return Storage::disk(CvFile::DISK)->download($cv_file->file_path, Str::slug($cv_file->label).'-cv.pdf');
    }
}
