@php
    $photo = $testimonial->getFirstMedia('photo');
    $initials = collect(preg_split('/\s+/', trim($testimonial->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $role = collect([$testimonial->position, $testimonial->company])->filter()->implode(', ');
@endphp

<figure class="quote-card card flex h-full flex-col" data-reveal>
    <blockquote class="pr-8 font-serif text-lg leading-relaxed text-fg">
        <p>{{ $testimonial->quote }}</p>
    </blockquote>
    <figcaption class="mt-auto flex items-center gap-4 pt-6">
        @if ($photo)
            <img src="{{ $photo->hasGeneratedConversion('thumb') ? $photo->getUrl('thumb') : $photo->getUrl() }}" alt="" width="48" height="48" loading="lazy" decoding="async" class="size-12 rounded-full border border-line object-cover">
        @else
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full border border-line bg-bg font-mono text-sm text-muted" aria-hidden="true">{{ $initials }}</span>
        @endif
        <span>
            <span class="block font-medium text-fg">{{ $testimonial->name }}</span>
            @if ($role)<span class="block text-sm text-muted">{{ $role }}</span>@endif
        </span>
    </figcaption>
</figure>
