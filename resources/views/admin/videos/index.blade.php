@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Videos' => null]])

@section('title', 'Videos')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.videos.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif

            <a href="{{ route('admin.videos.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add video</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search title or topic" aria-label="Search"></div>
                <div class="col-md-3">
                    <select name="state" class="form-select form-select-sm" aria-label="State">
                        <option value="">All states</option>
                        <option value="published" @selected(request('state') === 'published')>Published</option>
                        <option value="draft" @selected(request('state') === 'draft')>Unpublished</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="topic" class="form-select form-select-sm" aria-label="Topic">
                        <option value="">All topics</option>
                        @foreach ($topics as $topic)<option value="{{ $topic }}" @selected(request('topic') === $topic)>{{ $topic }}</option>@endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->hasAny(['q', 'state', 'topic']))<a href="{{ route('admin.videos.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.videos', 'publishable' => true])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th>
                        <th style="width:7rem"></th>
                        <th>Video</th>
                        <th>Topic</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr data-id="{{ $record->id }}">
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            <td><img src="{{ $record->thumbnailUrl('mqdefault') }}" alt="" width="96" height="54" loading="lazy" class="rounded object-fit-cover"></td>
                            <td>
                                <a href="{{ route('admin.videos.edit', $record) }}" class="fw-semibold text-decoration-none">{{ $record->title }}</a>
                                @if ($record->is_featured)<span class="badge text-bg-warning ms-1">Featured</span>@endif
                                <div class="small text-body-secondary">{{ $record->publicPath() }}</div>
                            </td>
                            <td>{{ $record->topic }}</td>
                            <td><x-admin.status-badge :status="$record->is_published ? 'published' : 'draft'" /></td>
                            <td class="text-nowrap small text-body-secondary">{{ $record->published_at?->format('j M Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.videos.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.videos.destroy', $record)" label="" aria-label="Move to Trash" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No videos found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
