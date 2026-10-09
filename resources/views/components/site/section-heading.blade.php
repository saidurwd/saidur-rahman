@props(['eyebrow' => null, 'title', 'level' => 2])

{{-- Page titles (level 1) are above the fold and often the largest paint, so they are never faded in. --}}
<div {{ $attributes->class('section-heading') }} @if ($level !== 1) data-reveal @endif>
    @if ($eyebrow)<p class="eyebrow mb-4">{{ $eyebrow }}</p>@endif
    <h{{ $level }} class="{{ $level === 1 ? 'text-h1' : 'text-h2' }} text-fg">{{ $title }}</h{{ $level }}>
    @if ($slot->isNotEmpty())
        <p class="mt-4 text-lg text-muted">{{ $slot }}</p>
    @endif
</div>
