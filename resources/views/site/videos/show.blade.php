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

    {{-- Privacy-enhanced embed: youtube-nocookie.com sets no cookies until the visitor plays. Lazy-loaded so it never slows the page. --}}
    <div class="mt-8 w-full max-w-4xl overflow-hidden rounded border border-line bg-black">
        {{-- The 16:9 ratio is set on the iframe itself, so the height always follows the width. --}}
        <iframe src="{{ $video->embedUrl() }}" title="{{ $video->title }}" class="block aspect-video w-full border-0" style="aspect-ratio: 16 / 9; height: auto" loading="lazy"
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    <p class="mt-3 text-sm text-muted">Can’t see the player? <a href="{{ $video->watchUrl() }}" class="link" target="_blank" rel="noopener">Watch on YouTube</a></p>

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
