@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Projects' => null]])

@section('title', 'Projects')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.projects.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif
            <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New project</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search title or client" aria-label="Search"></div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" aria-label="Status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ \Illuminate\Support\Str::headline($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->hasAny(['q', 'status']))<a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.projects', 'reorderable' => $reorderable])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th>
                        @if ($reorderable)<th style="width:2rem"></th>@endif
                        <th>Title</th><th>Client</th><th>Status</th><th>Featured</th><th></th>
                    </tr>
                </thead>
                <tbody @if ($reorderable) data-sortable data-reorder-url="{{ route('admin.projects.reorder') }}" @endif>
                    @forelse ($projects as $project)
                        <tr data-id="{{ $project->id }}">
                            <td><input type="checkbox" name="ids[]" value="{{ $project->id }}" form="bulk" class="form-check-input" aria-label="Select {{ $project->title }}"></td>
                            @if ($reorderable)<td data-drag-handle style="cursor:grab" class="text-body-secondary"><i class="bi bi-grip-vertical"></i></td>@endif
                            <td>
                                <a href="{{ route('admin.projects.edit', $project) }}" class="fw-semibold text-decoration-none">{{ $project->title }}</a>
                                <div class="small text-body-secondary">{{ $project->publicPath() }}</div>
                            </td>
                            <td>{{ $project->client }}</td>
                            <td><x-admin.status-badge :status="$project->status" /></td>
                            <td>@if ($project->is_featured)<i class="bi bi-star-fill text-warning" title="Featured"></i><span class="visually-hidden">Featured</span>@endif</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.projects.destroy', $project)" label="" :aria-label="'Move '.$project->title.' to Trash'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No projects found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <x-slot:footer>{{ $projects->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
