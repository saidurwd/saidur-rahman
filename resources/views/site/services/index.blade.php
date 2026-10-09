@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Services" description="Consulting offers: ERP advisory, IT audit and digital transformation." :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Services' => null]" />
    <div class="mt-8"><x-site.section-heading :level="1" eyebrow="Services" title="How I can help" /></div>

    @if ($services->isEmpty())
        <p class="mt-12 text-muted">Services will be listed soon.</p>
    @else
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @foreach ($services as $service)
                <a href="{{ route('services.show', $service->slug) }}" class="card block no-underline" data-reveal>
                    <h2 class="font-serif text-h3">{{ $service->title }}</h2>
                    @if ($service->summary)<p class="mt-2 text-muted">{{ $service->summary }}</p>@endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
