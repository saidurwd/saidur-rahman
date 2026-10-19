@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Redirects' => null]])

@section('title', 'Redirects')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            <a href="{{ route('admin.redirects.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New redirect</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <p class="small text-body-secondary mb-3">Redirects are added automatically when you change the slug of a project, expertise or page. Add your own here for old addresses.</p>
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search paths" aria-label="Search"></div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->filled('q'))<a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.redirects', 'publishable' => false, 'reorderable' => false])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th><th>From</th><th>To</th><th>Code</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            <td><code>{{ $record->from_path }}</code></td>
                            <td><code>{{ $record->to_path }}</code></td>
                            <td>{{ $record->code }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.redirects.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.redirects.destroy', $record)" label="" confirm="Delete this redirect?" aria-label="Delete redirect" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No redirects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
