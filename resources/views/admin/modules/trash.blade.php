@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), $label => route("{$routeBase}.index"), 'Trash' => null]])

@section('title', "{$label} in Trash")

@section('content')
    <x-admin.card flush>
        <x-admin.table :headers="['Name', 'Deleted', '']" empty="Trash is empty.">
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->{$labelAttribute} }}</td>
                    <td>{{ $record->deleted_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        <form method="POST" action="{{ route("{$routeBase}.restore", $record->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">Restore</button>
                        </form>
                        <x-admin.delete-button :action="route("{$routeBase}.force-delete", $record->id)" label="Delete forever" confirm="Delete this permanently? This cannot be undone." />
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
        @if ($records->hasPages())
            <x-slot:footer>{{ $records->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
    <a href="{{ route("{$routeBase}.index") }}" class="btn btn-link">&larr; Back to {{ strtolower($label) }}</a>
@endsection
