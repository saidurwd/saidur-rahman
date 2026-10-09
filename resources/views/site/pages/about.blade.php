@extends('site.layouts.app')

@php $settings = app(\App\Services\SettingService::class); @endphp

@section('seo')
    <x-site.seo title="About" :description="\Illuminate\Support\Str::limit((string) ($settings->get('positioning') ?: $settings->get('bio')), 160)" :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['About' => null]" />

    <header class="mt-8 grid items-start gap-12 lg:grid-cols-[1.4fr_0.6fr]">
        <div>
            <x-site.section-heading :level="1" eyebrow="About" :title="$settings->get('headline') ?: 'About me'" />
            @if ($settings->get('bio'))
                <div class="prose-site mt-8" data-reveal>
                    @foreach (preg_split('/\R{2,}/', (string) $settings->get('bio')) as $paragraph)<p>{{ trim($paragraph) }}</p>@endforeach
                </div>
            @endif
            @if ($settings->get('location'))<p class="label mt-8">{{ $settings->get('location') }}</p>@endif
        </div>
        @if ($portrait)
            <figure class="relative mx-auto w-full max-w-xs lg:max-w-none">
                <img src="{{ $portrait }}" alt="Portrait of {{ $siteName }}" width="900" height="900" class="aspect-[4/5] w-full rounded object-cover">
                <span class="pointer-events-none absolute -left-3 -top-3 size-10 border-l-2 border-t-2 border-highlight"></span>
                <span class="pointer-events-none absolute -bottom-3 -right-3 size-10 border-b-2 border-r-2 border-highlight"></span>
            </figure>
        @endif
    </header>

    @if ($settings->get('philosophy'))
        <section class="mt-20 border-t border-line pt-14">
            <p class="eyebrow mb-6" data-reveal>Leadership philosophy</p>
            <blockquote class="max-w-3xl font-serif text-h2 text-fg" data-reveal>{{ $settings->get('philosophy') }}</blockquote>
        </section>
    @endif

    @if ($skillGroups->isNotEmpty())
        <section class="mt-20 border-t border-line pt-14">
            <x-site.section-heading eyebrow="Skills" title="What I work with" />
            <div class="mt-10 grid gap-x-10 gap-y-10 md:grid-cols-2">
                @foreach ($skillGroups as $group)
                    <div data-reveal>
                        <h3 class="font-serif text-h3">{{ $group->name }}</h3>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($group->skills as $skill)
                                <li><x-site.tag>{{ $skill->name }}@if ($skill->years) · {{ $skill->years }}y @endif</x-site.tag></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($certifications->isNotEmpty() || $education->isNotEmpty())
        <section class="mt-20 grid gap-14 border-t border-line pt-14 md:grid-cols-2">
            @if ($certifications->isNotEmpty())
                <div>
                    <x-site.section-heading eyebrow="Credentials" title="Certifications" />
                    <ul class="mt-8 space-y-5">
                        @foreach ($certifications as $certification)
                            <li data-reveal>
                                <p class="font-medium">
                                    @if ($certification->credential_url)<a href="{{ $certification->credential_url }}" class="link" rel="noopener" target="_blank">{{ $certification->name }}</a>@else{{ $certification->name }}@endif
                                </p>
                                <p class="label mt-1">{{ $certification->issuer }}@if ($certification->issued_at) · {{ $certification->issued_at->format('Y') }}@endif</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($education->isNotEmpty())
                <div>
                    <x-site.section-heading eyebrow="Education" title="Degrees" />
                    <ul class="mt-8 space-y-5">
                        @foreach ($education as $item)
                            <li data-reveal>
                                <p class="font-medium">{{ $item->degree }}@if ($item->field), {{ $item->field }}@endif</p>
                                <p class="label mt-1">{{ $item->institution }}@if ($item->start_year || $item->end_year) · {{ collect([$item->start_year, $item->end_year])->filter()->implode('–') }}@endif</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    @endif

    <div class="mt-20 flex flex-wrap gap-4 border-t border-line pt-10">
        <x-site.button :href="route('experience')">See my experience</x-site.button>
        <x-site.button :href="route('projects.index')" variant="outline">View work</x-site.button>
    </div>
</div>
@endsection
