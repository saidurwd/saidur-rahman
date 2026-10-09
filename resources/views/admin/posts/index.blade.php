@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Articles' => null]])

@section('title', 'Articles')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.posts.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif
            <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-folder2"></i> Categories</a>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-tags"></i> Tags</a>
            <a href="{{ route('admin.posts.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New article</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search title or excerpt" aria-label="Search"></div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" aria-label="Status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ \Illuminate\Support\Str::headline($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->hasAny(['q', 'status', 'category']))<a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.posts', 'publishable' => true, 'reorderable' => false])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th class="text-end">Views</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $record) }}" class="fw-semibold text-decoration-none">{{ $record->title }}</a>
                                <div class="small text-body-secondary">{{ $record->publicPath() }} · {{ $record->reading_minutes }} min read</div>
                            </td>
                            <td>{{ $record->category?->name }}</td>
                            <td><x-admin.status-badge :status="$record->status" /></td>
                            <td class="text-nowrap">{{ $record->published_at?->format('j M Y, H:i') }}</td>
                            <td class="text-end">{{ number_format($record->views) }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.posts.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.posts.destroy', $record)" label="" aria-label="Move to Trash" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No articles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
