<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Testimonial::class;
    }

    protected function routeBase(): string
    {
        return 'admin.testimonials';
    }

    protected function moduleLabel(): string
    {
        return 'Testimonials';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Testimonial::class);

        $filtered = $request->hasAny(['q', 'state']);

        return view('admin.testimonials.index', [
            'records' => Testimonial::query()
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
        $this->authorize('create', Testimonial::class);

        return view('admin.testimonials.form', ['record' => new Testimonial(['is_approved' => false])]);
    }

    public function store(SaveTestimonialRequest $request): RedirectResponse
    {
        $record = Testimonial::create($request->safe()->except(['photo', 'remove_photo']) + ['sort_order' => Testimonial::nextSortOrder()]);
        $this->syncPhoto($request, $record);

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial created.');
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.testimonials.form', ['record' => $testimonial]);
    }

    public function update(SaveTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->safe()->except(['photo', 'remove_photo']));
        $this->syncPhoto($request, $testimonial);

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial saved.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial moved to Trash.');
    }

    private function syncPhoto(SaveTestimonialRequest $request, Testimonial $testimonial): void
    {
        if ($request->boolean('remove_photo')) {
            $testimonial->clearMediaCollection('photo');
        }

        if ($request->hasFile('photo')) {
            $testimonial->addMediaFromRequest('photo')->toMediaCollection('photo');
        }
    }
}
