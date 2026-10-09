@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Activity log' => null]])

@section('title', 'Activity log')

@section('content')
    <x-admin.card flush>
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <select name="type" class="form-select form-select-sm" aria-label="Record type">
                        <option value="">All record types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="event" class="form-select form-select-sm" aria-label="Event">
                        <option value="">All events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ ucfirst($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if (request()->hasAny(['type', 'event']))<a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-link">Clear</a>@endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>When</th><th>Who</th><th>Event</th><th>Record</th><th>Changes</th></tr></thead>
                <tbody>
                    @forelse ($activities as $activity)
                        @php
                            $changes = collect($activity->attribute_changes['attributes'] ?? []);
                            $old = collect($activity->attribute_changes['old'] ?? []);
                        @endphp
                        <tr>
                            <td class="text-nowrap" title="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</td>
                            <td>{{ $activity->causer?->name ?? 'System' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $activity->event ?? $activity->description }}</span></td>
                            <td>
                                {{ class_basename((string) $activity->subject_type) }} #{{ $activity->subject_id }}
                                @php $subject = $activity->subject; $name = $subject?->title ?? $subject?->name ?? $subject?->label ?? $subject?->company ?? null; @endphp
                                @if ($name)<div class="small text-body-secondary">{{ \Illuminate\Support\Str::limit((string) $name, 50) }}</div>@endif
                            </td>
                            <td>
                                @if ($changes->isNotEmpty())
                                    <details>
                                        <summary class="small">{{ $changes->count() }} {{ \Illuminate\Support\Str::plural('field', $changes->count()) }}</summary>
                                        <dl class="small mb-0 mt-1">
                                            @foreach ($changes as $field => $value)
                                                <dt class="fw-normal text-body-secondary">{{ $field }}</dt>
                                                <dd class="mb-1">
                                                    @if ($old->has($field))<s class="text-body-secondary">{{ \Illuminate\Support\Str::limit(is_scalar($old[$field]) ? (string) $old[$field] : json_encode($old[$field]), 60) }}</s> → @endif
                                                    {{ \Illuminate\Support\Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 60) }}
                                                </dd>
                                            @endforeach
                                        </dl>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())
            <x-slot:footer>{{ $activities->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
@endsection
