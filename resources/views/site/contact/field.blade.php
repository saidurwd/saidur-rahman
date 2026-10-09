@php
    $type = $type ?? 'text';
    $has = $errors->has($name);
@endphp

<div>
    <label for="{{ $name }}" class="mb-2 block font-medium">{{ $label }}@if (! empty($required)) <span class="text-accent" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" @if (! empty($required)) required @endif
        @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif maxlength="190"
        aria-invalid="{{ $has ? 'true' : 'false' }}" @if ($has) aria-describedby="{{ $name }}-error" @endif
        class="w-full rounded border bg-surface px-3 py-2 text-fg {{ $has ? 'border-red-600' : 'border-line' }}">
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
</div>
