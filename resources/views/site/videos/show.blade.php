@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$video->seo_title ?: $video->title" :description="$video->seo_description ?: Str::limit((string) $video->description, 160)" :schema="$schema" />
@endsection

@section('content')
<article class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Videos' => route('videos.index'), $video->title => null]" />

    <h1 class="mt-8 max-w-3xl font-serif text-h1 text-fg">{{ $video->title }}</h1>
    <p class="label mt-4">
        <time datetime="{{ $video->published_at?->toDateString() }}">{{ $video->published_at?->format('j F Y') }}</time>
        @if ($video->topic)· <a href="{{ route('videos.index', ['topic' => $video->topic]) }}" class="no-underline hover:text-accent">{{ $video->topic }}</a>@endif
    </p>

    {{-- Click-to-play: nothing is loaded from YouTube until the visitor presses play. Without JavaScript the link opens YouTube. --}}
    <div class="mt-8 max-w-4xl overflow-hidden rounded border border-line bg-black" x-data="{ playing: false }">
        <div class="relative aspect-video">
            <template x-if="playing">
                <iframe src="{{ $video->embedUrl(true) }}" title="{{ $video->title }}" class="absolute inset-0 h-full w-full" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </template>
            <a href="{{ $video->watchUrl() }}" x-show="!playing" @click.prevent="playing = true" target="_blank" rel="noopener" class="group absolute inset-0 block no-underline" aria-label="Play video: {{ $video->title }}">
                <img src="{{ $video->thumbnailUrl() }}" alt="" width="480" height="360" decoding="async" class="h-full w-full object-cover">
                <span class="absolute inset-0 grid place-items-center">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-black/75 text-white transition-transform group-hover:scale-105 motion-reduce:transition-none" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="ml-1 h-7 w-7 fill-current"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </span>
            </a>
        </div>
    </div>
    <p class="mt-3 text-sm text-muted">Playing loads the video from YouTube. <a href="{{ $video->watchUrl() }}" class="link" target="_blank" rel="noopener">Watch on YouTube</a></p>

    @if ($video->description)
        <div class="prose-site mt-10 max-w-3xl">{!! nl2br(e($video->description)) !!}</div>
    @endif

    @if ($related->isNotEmpty())
        <section class="mt-16 border-t border-line pt-10" aria-labelledby="more-videos">
            <h2 id="more-videos" class="font-serif text-h2 text-fg">More to watch</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $other)@include('site.partials.video-card', ['video' => $other])@endforeach
            </div>
        </section>
    @endif
</article>
@endsection
