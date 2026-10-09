<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyBulkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkActionRequest;
use App\Http\Requests\Admin\SaveRedirectRequest;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Redirects have no Trash, order or publish state, so this module does not use ManagesModule. */
class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Redirect::class);
        $term = $request->string('q')->toString();

        return view('admin.redirects.index', [
            'records' => Redirect::query()
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('from_path', 'like', "%{$term}%")->orWhere('to_path', 'like', "%{$term}%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Redirect::class);

        return view('admin.redirects.form', ['record' => new Redirect(['code' => 301])]);
    }

    public function store(SaveRedirectRequest $request): RedirectResponse
    {
        Redirect::create($request->validated());

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect created.');
    }

    public function edit(Redirect $redirect): View
    {
        $this->authorize('update', $redirect);

        return view('admin.redirects.form', ['record' => $redirect]);
    }

    public function update(SaveRedirectRequest $request, Redirect $redirect): RedirectResponse
    {
        $redirect->update($request->validated());

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect saved.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $this->authorize('delete', $redirect);
        $redirect->delete();

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect deleted.');
    }

    public function bulk(BulkActionRequest $request, ApplyBulkAction $apply): RedirectResponse
    {
        $this->authorize('delete', Redirect::class);
        $count = $apply(Redirect::class, 'delete', $request->validated('ids'));

        return back()->with('status', "{$count} redirect(s) deleted.");
    }
}
