@props(['role', 'company', 'period', 'current' => false, 'open' => false])

<li {{ $attributes->class(['timeline-node', 'is-current' => $current]) }}
    x-data="expandable({{ $open ? 'true' : 'false' }})" :data-open="open.toString()" data-reveal>
    <button type="button" class="group w-full text-left" @click="toggle()" :aria-expanded="open.toString()">
        <span class="label block">{{ $period }}</span>
        <span class="mt-1 flex items-start justify-between gap-4">
            <span>
                <span class="block font-serif text-h3 text-fg group-hover:text-accent">{{ $role }}</span>
                <span class="block text-muted">{{ $company }}</span>
            </span>
            <svg class="mt-2 size-4 shrink-0 text-muted transition-transform motion-reduce:transition-none" :class="open && 'rotate-180'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m3 6 5 5 5-5"/></svg>
        </span>
    </button>
    <div x-show="open" x-cloak class="mt-3 text-muted">
        {{ $slot }}
    </div>
</li>
