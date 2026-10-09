@extends('site.layouts.app')

@php
    $url = url($post->publicPath());
    $share = [
        'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url),
        'X' => 'https://twitter.com/intent/tweet?url='.rawurlencode($url).'&text='.rawurlencode($post->title),
        'Email' => 'mailto:?subject='.rawurlencode($post->title).'&body='.rawurlencode($url),
    ];
@endphp

@section('seo')
    <x-site.seo :title="$post->seo_title ?: $post->title" :description="$post->seo_description ?: $post->excerpt" :image="$image" type="article"
        :published-at="$post->published_at?->toIso8601String()" :schema="$schema" />
@endsection

@section('content')
<div x-data="readingProgress" @scroll.window.passive="update()" @resize.window.passive="update()" aria-hidden="true"
     class="no-print fixed inset-x-0 top-0 z-50 h-0.5 bg-transparent">
    <div class="h-full bg-accent" :style="`width: ${progress}%`"></div>
</div>

<article id="article" class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Articles' => route('blog.index'), $post->title => null]" />

    <header class="mt-8 max-w-3xl">
        <p class="eyebrow mb-5">
            @if ($post->category)<a href="{{ route('blog.category', $post->category->slug) }}" class="no-underline hover:underline">{{ $post->category->name }}</a>@else Article @endif
        </p>
        <h1 class="text-h1 text-fg">{{ $post->title }}</h1>
        @if ($post->excerpt)<p class="mt-6 text-lg text-muted md:text-xl">{{ $post->excerpt }}</p>@endif
        <p class="label mt-6">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time>
            · {{ $post->reading_minutes }} min read
        </p>
    </header>

    @if ($cover)
        <figure class="mt-10">
            <x-site.image :media="$cover" :eager="true" sizes="(min-width: 72rem) 1100px, 100vw" class="rounded border border-line" />
        </figure>
    @endif

    <div class="mt-12 grid gap-12 lg:grid-cols-[1fr_16rem]">
        <div>
            <div class="prose-site" data-copy-headings>{!! $article['html'] !!}</div>

            @if ($post->tags->isNotEmpty())
                <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach ($post->tags as $tag)
                        <li><a href="{{ route('blog.tag', $tag->slug) }}" class="tag no-underline hover:border-accent hover:text-accent">{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <section class="mt-10 border-t border-line pt-6 no-print" aria-label="Share this article">
                <p class="label mb-3">Share</p>
                <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    @foreach ($share as $label => $href)
                        <li><a href="{{ $href }}" class="link" rel="noopener noreferrer" @unless ($label === 'Email') target="_blank" @endunless>{{ $label }}</a></li>
                    @endforeach
                    <li>
                        <button type="button" x-data="copyLink(@js($url))" @click="copy()" class="link cursor-pointer">
                            <span x-text="copied ? 'Link copied' : 'Copy link'">Copy link</span>
                        </button>
                    </li>
                </ul>
            </section>
        </div>

        @if (count($article['toc']) > 1)
            <aside class="no-print order-first lg:order-none">
                <nav class="lg:sticky lg:top-24" aria-label="On this page">
                    <p class="label mb-3">On this page</p>
                    <ol class="space-y-2 border-l border-line pl-4 text-sm">
                        @foreach ($article['toc'] as $item)
                            <li class="{{ $item['level'] === 3 ? 'pl-3' : '' }}"><a href="#{{ $item['id'] }}" class="text-muted no-underline hover:text-accent">{{ $item['text'] }}</a></li>
                        @endforeach
                    </ol>
                </nav>
            </aside>
        @endif
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-20 border-t border-line pt-12 no-print">
            <x-site.section-heading eyebrow="Keep reading" title="Related articles" />
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($related as $other)@include('site.partials.post-card', ['post' => $other])@endforeach
            </div>
        </section>
    @endif
</article>
@endsection
