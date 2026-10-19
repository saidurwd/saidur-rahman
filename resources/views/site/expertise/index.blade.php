@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Expertise" description="Consulting offers: ERP advisory, IT audit and digital transformation." :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Expertise' => null]" />
    <div class="mt-8"><x-site.section-heading :level="1" eyebrow="Expertise" title="How I can help" /></div>

    @if ($expertises->isEmpty())
        <p class="mt-12 text-muted">Expertise areas will be listed soon.</p>
    @else
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @foreach ($expertises as $expertise)
                <a href="{{ route('expertise.show', $expertise->slug) }}" class="card block no-underline" data-reveal>
                    <h2 class="font-serif text-h3">{{ $expertise->title }}</h2>
                    @if ($expertise->summary)<p class="mt-2 text-muted">{{ $expertise->summary }}</p>@endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
