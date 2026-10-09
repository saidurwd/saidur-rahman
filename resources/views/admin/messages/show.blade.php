@php
    use App\Enums\MessageStatus;
    $quoted = "\n\n\nOn ".$message->created_at->format('j F Y, H:i').', '.$message->name." wrote:\n> ".str_replace("\n", "\n> ", trim($message->body));
    $reply = 'mailto:'.$message->email.'?subject='.rawurlencode('Re: '.($message->subject ?: 'your message')).'&body='.rawurlencode($quoted);
@endphp

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Messages' => route('admin.messages.index'), $message->name => null]])

@section('title', $message->subject ?: 'Message from '.$message->name)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <x-admin.card>
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div>
                    <div class="fs-5 fw-semibold">{{ $message->name }}@if ($message->company) <span class="text-body-secondary fw-normal">· {{ $message->company }}</span>@endif</div>
                    <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                </div>
                <div class="text-end small text-body-secondary">
                    <div title="{{ $message->created_at }}">{{ $message->created_at->format('j M Y, H:i') }}</div>
                    <x-admin.status-badge :status="$message->status" />
                </div>
            </div>
            @if ($message->subject)<h2 class="h5">{{ $message->subject }}</h2>@endif
            <div class="border rounded p-3 bg-body-tertiary" style="white-space: pre-wrap">{{ $message->body }}</div>
        </x-admin.card>

        <x-admin.card title="Details">
            <dl class="row small mb-0">
                <dt class="col-sm-3">IP address</dt><dd class="col-sm-9">{{ $message->ip ?: 'unknown' }}</dd>
                <dt class="col-sm-3">Browser</dt><dd class="col-sm-9 text-break">{{ $message->user_agent ?: 'unknown' }}</dd>
                <dt class="col-sm-3">First read</dt><dd class="col-sm-9">{{ $message->read_at?->format('j M Y, H:i') ?: 'not yet' }}</dd>
            </dl>
        </x-admin.card>
    </div>

    <div class="col-lg-4">
        <x-admin.card title="Actions">
            <a href="{{ $reply }}" class="btn btn-primary w-100 mb-2"><i class="bi bi-reply"></i> Reply by email</a>

            <form method="POST" action="{{ route('admin.messages.status', $message) }}" class="mb-2">
                @csrf
                <input type="hidden" name="status" value="replied">
                <button type="submit" class="btn btn-outline-success w-100" @disabled($message->status === MessageStatus::Replied)><i class="bi bi-check2-circle"></i> Mark as replied</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.star', $message) }}" class="mb-2">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-star{{ $message->starred ? '-fill text-warning' : '' }}"></i> {{ $message->starred ? 'Remove star' : 'Star' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.status', $message) }}" class="mb-2">
                @csrf
                <input type="hidden" name="status" value="new">
                <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-envelope"></i> Mark as unread</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.status', $message) }}">
                @csrf
                <input type="hidden" name="status" value="{{ $message->status === MessageStatus::Spam ? 'read' : 'spam' }}">
                <button type="submit" class="btn btn-outline-warning w-100"><i class="bi bi-shield-exclamation"></i> {{ $message->status === MessageStatus::Spam ? 'Not spam' : 'Mark as spam' }}</button>
            </form>
        </x-admin.card>
        <x-admin.delete-button :action="route('admin.messages.destroy', $message)" label="Move to Trash" />
    </div>
</div>
@endsection
