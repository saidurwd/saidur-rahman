@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Media' => null]])

@section('title', 'Media')

@section('content')
    <x-admin.card flush>
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <select name="type" class="form-select form-select-sm" aria-label="Belongs to">
                        <option value="">All records</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-primary" type="submit">Filter</button></div>
            </form>
            <p class="small text-body-secondary mt-3 mb-0">Images are added and removed on the project or testimonial they belong to.</p>
        </div>

        <div class="p-3">
            <div class="row g-3">
                @forelse ($media as $item)
                    @php $record = $item->model; $name = $record?->title ?? $record?->name ?? ('#'.$item->model_id); @endphp
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card h-100 mb-0">
                            @if (str_starts_with((string) $item->mime_type, 'image/'))
                                <a href="{{ $item->getUrl() }}" target="_blank" rel="noopener"><img src="{{ $item->hasGeneratedConversion('thumb') ? $item->getUrl('thumb') : $item->getUrl() }}" alt="{{ $item->getCustomProperty('alt') }}" class="card-img-top object-fit-cover" style="aspect-ratio: 4/3" loading="lazy"></a>
                            @endif
                            <div class="card-body p-2 small">
                                <div class="text-truncate fw-semibold" title="{{ $item->file_name }}">{{ $item->file_name }}</div>
                                <div class="text-body-secondary">{{ class_basename($item->model_type) }}: {{ \Illuminate\Support\Str::limit((string) $name, 24) }}</div>
                                <div class="text-body-secondary">{{ $item->human_readable_size }}@unless ($item->getCustomProperty('alt')) · <span class="text-warning">no alt text</span>@endunless</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-body-secondary py-4 mb-0">No images uploaded yet.</p>
                @endforelse
            </div>
        </div>
        @if ($media->hasPages())
            <x-slot:footer>{{ $media->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
