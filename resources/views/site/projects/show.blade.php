@extends('site.layouts.app')

@php
    $cover = $project->getFirstMedia('cover');
    $gallery = $project->getMedia('gallery');
    $sections = [
        ['Context and challenge', $project->problem],
        ['My role and solution', $project->solution],
        ['Architecture', $project->architecture],
        ['Outcome', $project->outcomes],
    ];
@endphp

@section('seo')
    <x-site.seo :title="$project->seo_title ?: $project->title" :description="$project->seo_description ?: $project->summary"
        :image="$cover?->hasGeneratedConversion('large') ? $cover->getUrl('large') : $cover?->getUrl()" :schema="$schema" />
@endsection

@section('content')
<article class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Projects' => route('projects.index'), $project->title => null]" />

    <header class="mt-8 max-w-4xl">
        <p class="eyebrow mb-5">{{ collect([$project->client, $project->period])->filter()->implode(' · ') ?: 'Case study' }}</p>
        <h1 class="text-display text-fg">{{ $project->title }}</h1>
        @if ($project->summary)<p class="mt-6 max-w-3xl text-lg text-muted md:text-xl">{{ $project->summary }}</p>@endif
    </header>

    @if ($cover)
        <figure class="mt-12">
            <x-site.image :media="$cover" :eager="true" sizes="(min-width: 72rem) 1100px, 100vw" class="rounded border border-line" />
        </figure>
    @endif

    <div class="mt-14 grid gap-12 lg:grid-cols-[16rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <dl class="space-y-5 border-t border-line pt-5">
                @foreach ([['Role', $project->role], ['Client', $project->client], ['Period', $project->period]] as [$term, $value])
                    @if ($value)<div><dt class="label">{{ $term }}</dt><dd class="mt-1">{{ $value }}</dd></div>@endif
                @endforeach
                @if ($project->tech_stack)
                    <div>
                        <dt class="label">Stack</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            @foreach ($project->tech_stack as $tech)<a href="{{ route('projects.index', ['tech' => $tech]) }}" class="tag no-underline hover:border-accent hover:text-accent">{{ $tech }}</a>@endforeach
                        </dd>
                    </div>
                @endif
                @if ($project->live_url || $project->repo_url)
                    <div class="flex flex-col gap-2 no-print">
                        @if ($project->live_url)<a href="{{ $project->live_url }}" class="link" rel="noopener" target="_blank">Live site ↗</a>@endif
                        @if ($project->repo_url)<a href="{{ $project->repo_url }}" class="link" rel="noopener" target="_blank">Source code ↗</a>@endif
                    </div>
                @endif
            </dl>
        </aside>

        <div class="space-y-14">
            @foreach ($sections as [$heading, $body])
                @if ($body)
                    <section data-reveal>
                        <p class="eyebrow mb-4">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h2 class="mb-6 text-h2">{{ $heading }}</h2>
                        <div class="prose-site">{!! $body !!}</div>
                    </section>
                @endif
            @endforeach

            @if ($gallery->isNotEmpty())
                <section data-reveal>
                    <h2 class="mb-6 text-h2">Gallery</h2>
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($gallery as $image)
                            <figure>
                                <x-site.image :media="$image" sizes="(min-width: 40rem) 45vw, 100vw" class="rounded border border-line" />
                                @if ($image->getCustomProperty('alt'))<figcaption class="label mt-2 normal-case tracking-normal">{{ $image->getCustomProperty('alt') }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    <nav class="mt-20 grid gap-4 border-t border-line pt-10 sm:grid-cols-2 no-print" aria-label="More case studies">
        @if ($neighbours['previous'])
            <a href="{{ route('projects.show', $neighbours['previous']->slug) }}" class="card no-underline">
                <p class="label mb-2">← Previous</p><p class="font-serif text-h3">{{ $neighbours['previous']->title }}</p>
            </a>
        @else<span></span>@endif
        @if ($neighbours['next'])
            <a href="{{ route('projects.show', $neighbours['next']->slug) }}" class="card text-right no-underline">
                <p class="label mb-2">Next →</p><p class="font-serif text-h3">{{ $neighbours['next']->title }}</p>
            </a>
        @endif
    </nav>
</article>
@endsection
