@php $cover = $project->getFirstMedia('cover'); @endphp

<a href="{{ route('projects.show', $project->slug) }}" class="card group flex h-full flex-col no-underline" data-reveal>
    @if ($cover)
        <div class="-mx-6 -mt-6 mb-5 overflow-hidden border-b border-line">
            <x-site.image :media="$cover" sizes="(min-width: 64rem) 33vw, (min-width: 40rem) 50vw, 100vw" class="aspect-[16/10] object-cover transition-transform duration-500 group-hover:scale-[1.02] motion-reduce:transition-none" />
        </div>
    @endif
    <p class="label mb-3">{{ collect([$project->client, $project->period])->filter()->implode(' · ') ?: 'Case study' }}</p>
    <h3 class="font-serif text-h3 text-fg group-hover:text-accent">{{ $project->title }}</h3>
    @if ($project->summary)<p class="mt-2 text-muted">{{ $project->summary }}</p>@endif
    @if ($project->tech_stack)
        <div class="mt-auto flex flex-wrap gap-2 pt-5">
            @foreach (array_slice($project->tech_stack, 0, 5) as $tech)<x-site.tag>{{ $tech }}</x-site.tag>@endforeach
        </div>
    @endif
</a>
