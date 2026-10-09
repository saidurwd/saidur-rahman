@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Projects" description="Case studies: the problem, my role, the architecture and the outcome." :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Projects' => null]" />

    <div class="mt-8">
        <x-site.section-heading :level="1" eyebrow="Projects" title="Case studies">Written like consulting reports: context, challenge, my role, architecture and outcome.</x-site.section-heading>
    </div>

    @if (count($tags) > 1)
        <nav class="mt-10 flex flex-wrap gap-2" aria-label="Filter by technology">
            <a href="{{ route('projects.index') }}" class="tag no-underline {{ $activeTech ? '' : 'tag-accent' }}" @unless ($activeTech) aria-current="true" @endunless>All</a>
            @foreach ($tags as $tag)
                <a href="{{ route('projects.index', ['tech' => $tag]) }}" class="tag no-underline {{ $activeTech === $tag ? 'tag-accent' : 'hover:border-accent hover:text-accent' }}" @if ($activeTech === $tag) aria-current="true" @endif>{{ $tag }}</a>
            @endforeach
        </nav>
    @endif

    @if ($projects->isEmpty())
        <p class="mt-14 text-muted">{{ $activeTech ? "No projects use {$activeTech} yet." : 'Case studies are coming soon.' }}</p>
    @else
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)@include('site.partials.project-card', ['project' => $project])@endforeach
        </div>
    @endif
</div>
@endsection
