@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = match ($value) {
        'published', 'active', 'approved', 'replied' => 'success',
        'scheduled' => 'info',
        'new' => 'primary',
        'archived' => 'warning',
        'spam' => 'danger',
        default => 'secondary',
    };
@endphp

<span {{ $attributes->class(["badge text-bg-{$tone}"]) }}>{{ \Illuminate\Support\Str::headline($value) }}</span>
