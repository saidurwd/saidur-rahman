@extends('site.layouts.app')

@php
    $settings = app(\App\Services\SettingService::class);
    $contactUrl = Route::has('contact') ? route('contact') : ($contactEmail ? 'mailto:'.$contactEmail : null);
@endphp

@section('seo')
    <x-site.seo :schema="$schema" />
@endsection

@section('content')
{{-- Hero --}}
<section class="blueprint overflow-hidden">
    <div class="wrap grid items-center gap-12 py-16 md:py-24 lg:grid-cols-[1.25fr_0.75fr]">
        <div>
            @if ($settings->get('headline'))<p class="eyebrow mb-6" data-reveal>{{ $settings->get('headline') }}</p>@endif
            <h1 class="text-display text-fg" data-reveal>{{ $settings->get('hero_title') ?: $siteName }}</h1>
            @if ($settings->get('positioning'))
                <p class="mt-6 max-w-2xl text-lg text-muted md:text-xl" data-reveal>{{ $settings->get('positioning') }}</p>
            @endif
            <div class="mt-9 flex flex-wrap items-center gap-4" data-reveal>
                <x-site.button :href="route('projects.index')">View work</x-site.button>
                @if ($hasCv)<x-site.button :href="url('/cv')" variant="outline">Download CV</x-site.button>@endif
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-sm lg:max-w-none" aria-hidden="{{ $portrait ? 'false' : 'true' }}">
            @if ($portrait)
                <figure class="relative">
                    <img src="{{ $portrait }}" alt="Portrait of {{ $siteName }}" width="900" height="900" fetchpriority="high"
                         class="aspect-[4/5] w-full rounded object-cover">
                    <span class="pointer-events-none absolute -left-3 -top-3 size-10 border-l-2 border-t-2 border-highlight"></span>
                    <span class="pointer-events-none absolute -bottom-3 -right-3 size-10 border-b-2 border-r-2 border-highlight"></span>
                </figure>
            @else
                <svg class="draw w-full text-accent" viewBox="0 0 360 300" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path pathLength="1" d="M60 70H160C200 70 200 150 240 150H300" />
                    <path pathLength="1" d="M60 70V230H180" />
                    <path pathLength="1" d="M180 230H300V150" />
                    <g fill="var(--color-bg)">
                        <circle cx="60" cy="70" r="10" /><circle cx="300" cy="150" r="10" /><circle cx="180" cy="230" r="10" />
                    </g>
                    <circle cx="60" cy="70" r="4" fill="var(--color-highlight)" stroke="none" />
                </svg>
            @endif
        </div>
    </div>
</section>

{{-- Numbers --}}
@if (! empty($stats))
    <section class="border-y border-line" aria-label="Key figures">
        <dl class="wrap grid grid-cols-2 gap-y-8 py-10 md:grid-cols-{{ min(count($stats), 4) }}">
            @foreach ($stats as $stat)
                <div data-reveal>
                    <dt class="label order-2 mt-2">{{ $stat['label'] }}</dt>
                    <dd class="font-mono text-4xl font-medium text-fg md:text-5xl" x-data="countUp(@js($stat['value']))" x-text="display">{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif

{{-- Capability pillars --}}
@if ($pillars->isNotEmpty())
    <section class="wrap py-20">
        <x-site.section-heading eyebrow="Capabilities" title="What I bring" />
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ($pillars as $group)
                <div class="card card-hover" data-reveal>
                    <p class="label mb-3">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                    <h3 class="font-serif text-h3">{{ $group->name }}</h3>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($group->skills as $skill)<li><x-site.tag>{{ $skill->name }}</x-site.tag></li>@endforeach
                    </ul>
                    <a href="{{ route('projects.index') }}" class="link mt-5 inline-block text-sm">See related work</a>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- Featured projects --}}
@if ($projects->isNotEmpty())
    <section class="border-t border-line py-20">
        <div class="wrap">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <x-site.section-heading eyebrow="Selected work" title="Systems I built and led" />
                <x-site.button :href="route('projects.index')" variant="quiet">All projects →</x-site.button>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)@include('site.partials.project-card', ['project' => $project])@endforeach
            </div>
        </div>
    </section>
@endif

{{-- Career preview --}}
@if ($experiences->isNotEmpty())
    <section class="border-t border-line py-20">
        <div class="wrap grid gap-12 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <x-site.section-heading eyebrow="Career" title="Two decades, one thread">From hands-on systems work to running IT for the business.</x-site.section-heading>
                <x-site.button :href="route('experience')" variant="quiet" class="mt-6">Full timeline →</x-site.button>
            </div>
            <ol class="timeline">
                @foreach ($experiences as $experience)
                    <x-site.timeline-node :role="$experience->role" :company="$experience->company"
                        :period="$experience->start_date->format('Y').' — '.($experience->is_current ? 'Present' : $experience->end_date?->format('Y'))"
                        :current="$experience->is_current" :open="$loop->first">
                        @include('site.partials.experience-detail', ['experience' => $experience])
                    </x-site.timeline-node>
                @endforeach
            </ol>
        </div>
    </section>
@endif

{{-- Latest articles --}}
@if ($posts->isNotEmpty())
    <section class="border-t border-line py-20">
        <div class="wrap">
            <x-site.section-heading eyebrow="Writing" title="Latest articles" />
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="card block no-underline" data-reveal>
                        <p class="label mb-3">{{ $post->published_at?->format('j M Y') }}</p>
                        <h3 class="font-serif text-h3">{{ $post->title }}</h3>
                        @if ($post->excerpt)<p class="mt-2 text-muted">{{ $post->excerpt }}</p>@endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Contact --}}
<section class="border-t border-line">
    <div class="wrap py-20">
        <div class="blueprint rounded border border-line bg-surface px-6 py-14 text-center md:px-12">
            <p class="eyebrow mb-5 justify-center">Get in touch</p>
            <h2 class="text-h1 mx-auto max-w-2xl">{{ $availability ?: 'Let’s talk about your next project or role.' }}</h2>
            @if ($contactUrl)<x-site.button :href="$contactUrl" class="mt-8">Contact me</x-site.button>@endif
        </div>
    </div>
</section>
@endsection
