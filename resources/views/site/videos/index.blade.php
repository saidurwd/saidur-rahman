@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$searching ? 'Search: '.$term : ($activeTopic ? $activeTopic.' videos' : 'Videos')"
        :description="$searching ? null : 'Talks, walkthroughs and lessons on IT leadership, ERP, Laravel and digital transformation.'"
        :canonical="$canonical" :noindex="$noindex" :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Videos' => null]" />

    <div class="mt-8 flex flex-wrap items-end justify-between gap-8">
        <x-site.section-heading :level="1" eyebrow="Watch" :title="$searching ? 'Search results' : ($activeTopic ?: 'Videos')">
            {{ $searching ? 'Videos matching “'.$term.'”.' : 'Talks, walkthroughs and lessons on IT leadership, ERP, Laravel and digital transformation.' }}
        </x-site.section-heading>

        <form method="GET" action="{{ route('videos.index') }}" role="search" class="flex w-full max-w-sm gap-2 no-print">
            <label for="q" class="sr-only">Search videos</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="Search videos" maxlength="100"
                class="w-full rounded border border-line bg-surface px-3 py-2 text-fg placeholder:text-muted">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    @if (count($topics) > 0)
        <nav class="mt-10 flex flex-wrap gap-2" aria-label="Filter by topic">
            <a href="{{ route('videos.index') }}" class="tag no-underline {{ ! $activeTopic && ! $searching ? 'tag-accent' : 'hover:border-accent hover:text-accent' }}">All</a>
            @foreach ($topics as $topic)
                <a href="{{ route('videos.index', ['topic' => $topic['name']]) }}" class="tag no-underline {{ $activeTopic === $topic['name'] ? 'tag-accent' : 'hover:border-accent hover:text-accent' }}" @if ($activeTopic === $topic['name']) aria-current="true" @endif>{{ $topic['name'] }} ({{ $topic['count'] }})</a>
            @endforeach
        </nav>
    @endif

    @if ($featured->isNotEmpty())
        <section class="mt-12" aria-labelledby="featured-videos">
            <h2 id="featured-videos" class="label">Featured</h2>
            <div class="mt-4 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $video)@include('site.partials.video-card', ['video' => $video])@endforeach
            </div>
        </section>
    @endif

    @if ($videos->isEmpty())
        <p class="mt-14 text-muted">
            @if ($searching)Nothing matched “{{ $term }}”. Try a different word, or <a href="{{ route('videos.index') }}" class="link">see all videos</a>.
            @elseif ($activeTopic)No videos in this topic yet. <a href="{{ route('videos.index') }}" class="link">See all videos</a>.
            @else Videos are coming soon.@endif
        </p>
    @else
        @if ($featured->isNotEmpty())<h2 class="label mt-14">All videos</h2>@endif
        <div class="{{ $featured->isNotEmpty() ? 'mt-4' : 'mt-12' }} grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($videos as $video)@include('site.partials.video-card', ['video' => $video])@endforeach
        </div>
        @include('site.partials.pagination', ['paginator' => $videos])
    @endif
</div>
@endsection
