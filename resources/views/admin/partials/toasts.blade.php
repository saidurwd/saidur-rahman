@php
    $toasts = collect([
        ['status', 'success', 'check-circle-fill'],
        ['warning', 'warning', 'exclamation-triangle-fill'],
        ['error', 'danger', 'x-octagon-fill'],
    ])->filter(fn ($t) => session()->has($t[0]));
@endphp

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090">
    @foreach ($toasts as [$key, $tone, $icon])
        <div class="toast align-items-center text-bg-{{ $tone }} border-0" role="status" aria-live="polite" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-{{ $icon }} me-2"></i>{{ session($key) }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endforeach
</div>
