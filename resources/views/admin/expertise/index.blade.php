@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Expertise' => null]])

@section('title', 'Expertise')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.expertise.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif

            <a href="{{ route('admin.expertise.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New expertise</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search" aria-label="Search"></div>
                <div class="col-md-3">
                    <select name="state" class="form-select form-select-sm" aria-label="State">
                        <option value="">All</option>
                        <option value="published" @selected(request('state') === 'published')>Published</option>
                        <option value="draft" @selected(request('state') === 'draft')>Unpublished</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->hasAny(['q', 'state']))<a href="{{ route('admin.expertise.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.expertise', 'publishable' => true, 'reorderable' => $reorderable])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th>
                        @if ($reorderable)<th style="width:2rem"></th>@endif
                        <th>Expertise</th>
                        <th>Icon</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody @if ($reorderable) data-sortable data-reorder-url="{{ route('admin.expertise.reorder') }}" @endif>
                    @forelse ($records as $record)
                        <tr data-id="{{ $record->id }}">
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            @if ($reorderable)<td data-drag-handle style="cursor:grab" class="text-body-secondary"><i class="bi bi-grip-vertical"></i></td>@endif
                            <td><a href="{{ route('admin.expertise.edit', $record) }}" class="fw-semibold text-decoration-none">{{ $record->title }}</a><div class="small text-body-secondary">{{ $record->publicPath() }}</div></td>
                            <td>{{ $record->icon }}</td>
                            <td><x-admin.status-badge :status="$record->is_published ? 'published' : 'draft'" /></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.expertise.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.expertise.destroy', $record)" label="" aria-label="Move to Trash" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">Nothing found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
