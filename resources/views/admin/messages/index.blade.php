@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Messages' => null]])

@section('title', 'Messages')

@section('content')
    @php
        $tabs = ['inbox' => 'Inbox', 'unread' => 'Unread', 'starred' => 'Starred', 'spam' => 'Spam'];
    @endphp

    <x-admin.card flush>
        <x-slot:actions>
            @if ($trashedCount)
                <a href="{{ route('admin.messages.trash') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-trash3"></i> Trash ({{ $trashedCount }})</a>
            @endif
            <a href="{{ route('admin.messages.export', request()->only(['folder', 'q'])) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export CSV</a>
        </x-slot:actions>

        <ul class="nav nav-tabs px-3 pt-2" role="tablist">
            @foreach ($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $folder === $key ? 'active' : '' }}" href="{{ route('admin.messages.index', ['folder' => $key]) }}" @if ($folder === $key) aria-current="page" @endif>
                        {{ $label }}
                        @if ($counts[$key] > 0)<span class="badge {{ $key === 'unread' ? 'text-bg-primary' : 'text-bg-secondary' }} ms-1">{{ $counts[$key] }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2" role="search">
                <input type="hidden" name="folder" value="{{ $folder }}">
                <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, email, subject or text" aria-label="Search"></div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
                    @if (request()->filled('q'))<a href="{{ route('admin.messages.index', ['folder' => $folder]) }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        <form id="bulk" method="POST" action="{{ route('admin.messages.bulk') }}" class="p-3 border-bottom d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <select name="action" class="form-select form-select-sm w-auto" aria-label="Bulk action">
                <option value="read">Mark as read</option>
                <option value="unread">Mark as unread</option>
                <option value="star">Star</option>
                <option value="unstar">Remove star</option>
                <option value="spam">Mark as spam</option>
                <option value="not-spam">Not spam</option>
                <option value="delete">Move to Trash</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Apply to selected</button>
            @error('ids')<span class="text-danger small">{{ $message }}</span>@enderror
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th><th style="width:2rem"></th><th>From</th><th>Message</th><th>Received</th></tr></thead>
                <tbody>
                    @forelse ($messages as $message)
                        @php $unread = $message->status === \App\Enums\MessageStatus::New; @endphp
                        <tr class="{{ $unread ? 'fw-semibold' : '' }}">
                            <td><input type="checkbox" name="ids[]" value="{{ $message->id }}" form="bulk" class="form-check-input" aria-label="Select message from {{ $message->name }}"></td>
                            <td>
                                <form method="POST" action="{{ route('admin.messages.star', $message) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 {{ $message->starred ? 'text-warning' : 'text-body-secondary' }}" aria-label="{{ $message->starred ? 'Remove star' : 'Star' }}" aria-pressed="{{ $message->starred ? 'true' : 'false' }}"><i class="bi bi-star{{ $message->starred ? '-fill' : '' }}"></i></button>
                                </form>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.messages.show', $message) }}" class="text-decoration-none">{{ $message->name }}</a>
                                <div class="small text-body-secondary fw-normal">{{ $message->company ?: $message->email }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.messages.show', $message) }}" class="text-decoration-none text-reset">{{ $message->subject ?: '(no subject)' }}</a>
                                @if ($message->status === \App\Enums\MessageStatus::Spam)<x-admin.status-badge :status="$message->status" class="ms-1" />@endif
                                @if ($message->status === \App\Enums\MessageStatus::Replied)<x-admin.status-badge :status="$message->status" class="ms-1" />@endif
                                <div class="small text-body-secondary fw-normal">{{ \Illuminate\Support\Str::limit($message->body, 110) }}</div>
                            </td>
                            <td class="text-nowrap small" title="{{ $message->created_at }}">{{ $message->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No messages here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            <x-slot:footer>{{ $messages->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
