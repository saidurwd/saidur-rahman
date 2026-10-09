@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$searching ? 'Search: '.$term : $heading" :description="$searching ? null : 'Writing on IT leadership, ERP, Laravel and digital transformation.'"
        :canonical="$canonical" :noindex="$noindex" :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="$breadcrumbs" />

    <div class="mt-8 flex flex-wrap items-end justify-between gap-8">
        <x-site.section-heading :level="1" eyebrow="Writing" :title="$searching ? 'Search results' : $heading">
            {{ $searching ? 'Articles matching “'.$term.'”.' : 'Notes on IT leadership, ERP, Laravel and digital transformation.' }}
        </x-site.section-heading>

        <form method="GET" action="{{ route('blog.index') }}" role="search" class="flex w-full max-w-sm gap-2 no-print">
            <label for="q" class="sr-only">Search articles</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="Search articles" maxlength="100"
                class="w-full rounded border border-line bg-surface px-3 py-2 text-fg placeholder:text-muted">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    @if (count($categories) > 0)
        <nav class="mt-10 flex flex-wrap gap-2" aria-label="Filter by category">
            <a href="{{ route('blog.index') }}" class="tag no-underline {{ ! $activeCategory && ! $searching && $breadcrumbs === ['Articles' => null] ? 'tag-accent' : 'hover:border-accent hover:text-accent' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ route('blog.category', $category['slug']) }}" class="tag no-underline {{ $activeCategory === $category['slug'] ? 'tag-accent' : 'hover:border-accent hover:text-accent' }}" @if ($activeCategory === $category['slug']) aria-current="true" @endif>{{ $category['name'] }} ({{ $category['count'] }})</a>
            @endforeach
        </nav>
    @endif

    @if ($posts->isEmpty())
        <p class="mt-14 text-muted">
            @if ($searching)Nothing matched “{{ $term }}”. Try a different word, or <a href="{{ route('blog.index') }}" class="link">see all articles</a>.
            @else Articles are coming soon.@endif
        </p>
    @else
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)@include('site.partials.post-card', ['post' => $post])@endforeach
        </div>
        @include('site.partials.pagination', ['paginator' => $posts])
    @endif
</div>
@endsection
