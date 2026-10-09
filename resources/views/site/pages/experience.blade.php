@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Experience" description="Career timeline: roles, scope and achievements." :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Experience' => null]" />

    <div class="mt-8">
        <x-site.section-heading :level="1" eyebrow="Experience" title="Career timeline">Each node opens to show scope and achievements.</x-site.section-heading>
    </div>

    @if ($experiences->isEmpty())
        <p class="mt-12 text-muted">Experience will be added soon.</p>
    @else
        <ol class="timeline mt-14 max-w-3xl">
            @foreach ($experiences as $experience)
                <x-site.timeline-node :role="$experience->role" :company="$experience->company"
                    :period="$experience->start_date->format('M Y').' — '.($experience->is_current ? 'Present' : $experience->end_date?->format('M Y'))"
                    :current="$experience->is_current" :open="$experience->is_current">
                    @include('site.partials.experience-detail', ['experience' => $experience])
                </x-site.timeline-node>
            @endforeach
        </ol>
    @endif
</div>
@endsection
