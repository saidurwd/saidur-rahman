@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Articles' => route('admin.posts.index'), 'Tags' => null]])

@section('title', 'Tags')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            <a href="{{ route('admin.tags.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New tag</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search tags" aria-label="Search"></div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->filled('q'))<a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.tags', 'publishable' => false, 'reorderable' => false])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th><th>Tag</th><th>Slug</th><th class="text-end">Articles</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            <td><a href="{{ route('admin.tags.edit', $record) }}" class="fw-semibold text-decoration-none">{{ $record->name }}</a></td>
                            <td><code>{{ $record->slug }}</code></td>
                            <td class="text-end">{{ $record->posts_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.tags.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.tags.destroy', $record)" label="" confirm="Delete this tag? It will be removed from its articles." aria-label="Delete tag" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No tags yet. Add them while writing an article.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
