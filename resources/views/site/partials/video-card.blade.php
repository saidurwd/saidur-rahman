<article class="card group flex h-full flex-col" data-reveal>
    <a href="{{ route('videos.show', $video->slug) }}" tabindex="-1" aria-hidden="true" class="-mx-6 -mt-6 mb-5 block overflow-hidden border-b border-line">
        <span class="relative block aspect-video bg-surface">
            <img src="{{ $video->thumbnailUrl('mqdefault') }}" alt="" width="320" height="180" loading="lazy" decoding="async"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02] motion-reduce:transition-none">
            <span class="absolute inset-0 grid place-items-center">
                <span class="grid h-12 w-12 place-items-center rounded-full bg-black/70 text-white" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="ml-0.5 h-5 w-5 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
        </span>
    </a>
    <p class="label mb-3"><time datetime="{{ $video->published_at?->toDateString() }}">{{ $video->published_at?->format('j M Y') }}</time></p>
    <h3 class="font-serif text-h3 text-fg">
        <a href="{{ route('videos.show', $video->slug) }}" class="no-underline after:absolute after:inset-0 hover:text-accent">{{ $video->title }}</a>
    </h3>
    @if ($video->description)<p class="mt-2 line-clamp-2 text-muted">{{ $video->description }}</p>@endif
    @if ($video->topic)
        <p class="mt-auto pt-5"><x-site.tag class="relative z-10">{{ $video->topic }}</x-site.tag></p>
    @endif
</article>
