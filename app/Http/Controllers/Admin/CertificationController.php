<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCertificationRequest;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificationController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Certification::class;
    }

    protected function routeBase(): string
    {
        return 'admin.certifications';
    }

    protected function moduleLabel(): string
    {
        return 'Certifications';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certification::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.certifications.index', [
            'records' => Certification::query()
                ->search($request->string('q')->toString())
                ->withState($request->string('state')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
            'reorderable' => ! $filtered,
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Certification::class);

        return view('admin.certifications.form', ['record' => new Certification(['is_published' => true])]);
    }

    public function store(SaveCertificationRequest $request): RedirectResponse
    {
        Certification::create($request->validated() + ['sort_order' => Certification::nextSortOrder()]);

        return redirect()->route('admin.certifications.index')->with('status', 'Certification created.');
    }

    public function edit(Certification $certification): View
    {
        $this->authorize('update', $certification);

        return view('admin.certifications.form', ['record' => $certification]);
    }

    public function update(SaveCertificationRequest $request, Certification $certification): RedirectResponse
    {
        $certification->update($request->validated());

        return redirect()->route('admin.certifications.index')->with('status', 'Certification saved.');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $this->authorize('delete', $certification);
        $certification->delete();

        return redirect()->route('admin.certifications.index')->with('status', 'Certification moved to Trash.');
    }
}
