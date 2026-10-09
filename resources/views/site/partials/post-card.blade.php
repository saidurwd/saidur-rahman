@php $cover = $post->getFirstMedia('cover'); @endphp

<article class="card group flex h-full flex-col" data-reveal>
    @if ($cover)
        <a href="{{ route('blog.show', $post->slug) }}" tabindex="-1" aria-hidden="true" class="-mx-6 -mt-6 mb-5 block overflow-hidden border-b border-line">
            <x-site.image :media="$cover" sizes="(min-width: 64rem) 33vw, (min-width: 40rem) 50vw, 100vw" class="aspect-[16/9] object-cover transition-transform duration-500 group-hover:scale-[1.02] motion-reduce:transition-none" />
        </a>
    @endif
    <p class="label mb-3">
        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
        · {{ $post->reading_minutes }} min read
    </p>
    <h3 class="font-serif text-h3 text-fg">
        <a href="{{ route('blog.show', $post->slug) }}" class="no-underline after:absolute after:inset-0 hover:text-accent">{{ $post->title }}</a>
    </h3>
    @if ($post->excerpt)<p class="mt-2 text-muted">{{ $post->excerpt }}</p>@endif
    @if ($post->category)
        <p class="mt-auto pt-5"><x-site.tag class="relative z-10">{{ $post->category->name }}</x-site.tag></p>
    @endif
</article>
