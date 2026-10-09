@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'CV files' => null]])

@section('title', 'CV files')

@section('content')
    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.cv-files.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif
            <a href="{{ route('admin.cv-files.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> Upload CV</a>
        </x-slot:actions>

        <div class="p-3 border-bottom">
            <p class="small text-body-secondary mb-0">One version per label is active. Active versions are offered on the public CV page.</p>
        </div>

        @include('admin.modules.partials.bulk-bar', ['routeBase' => 'admin.cv-files', 'publishable' => false, 'reorderable' => false])

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th><th>Label</th><th>Size</th><th>Status</th><th>Downloads</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk" class="form-check-input" aria-label="Select"></td>
                            <td><a href="{{ route('admin.cv-files.edit', $record) }}" class="fw-semibold text-decoration-none">{{ $record->label }}</a></td>
                            <td>{{ $record->sizeForHumans() }}</td>
                            <td><x-admin.status-badge :status="$record->is_active ? 'active' : 'draft'" /></td>
                            <td>{{ number_format($record->downloads) }}</td>
                            <td>{{ $record->created_at->format('j M Y') }}</td>
                            <td class="text-end text-nowrap">
                                @unless ($record->is_active)
                                    <form method="POST" action="{{ route('admin.cv-files.activate', $record) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Set active</button></form>
                                @endunless
                                <a href="{{ route('admin.cv-files.download', $record) }}" class="btn btn-sm btn-outline-secondary" aria-label="Download"><i class="bi bi-download"></i></a>
                                <a href="{{ route('admin.cv-files.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-admin.delete-button :action="route('admin.cv-files.destroy', $record)" label="" aria-label="Move to Trash" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No CV uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
