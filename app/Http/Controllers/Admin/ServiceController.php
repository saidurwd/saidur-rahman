<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveSlugged;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Service::class;
    }

    protected function routeBase(): string
    {
        return 'admin.services';
    }

    protected function moduleLabel(): string
    {
        return 'Services';
    }

    protected function labelAttribute(): string
    {
        return 'title';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.services.index', [
            'records' => Service::query()
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
        $this->authorize('create', Service::class);

        return view('admin.services.form', ['record' => new Service(['is_published' => false])]);
    }

    public function store(SaveServiceRequest $request, SaveSlugged $save): RedirectResponse
    {
        $save(new Service(['sort_order' => Service::nextSortOrder()]), $request->validated(), ['body'], ['summary']);

        return redirect()->route('admin.services.index')->with('status', 'Service created.');
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        return view('admin.services.form', ['record' => $service]);
    }

    public function update(SaveServiceRequest $request, Service $service, SaveSlugged $save): RedirectResponse
    {
        $save($service, $request->validated(), ['body'], ['summary']);

        return redirect()->route('admin.services.index')->with('status', 'Service saved.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Service moved to Trash.');
    }
}
