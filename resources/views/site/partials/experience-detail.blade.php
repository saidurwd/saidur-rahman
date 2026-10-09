@if ($experience->location || $experience->employment_type)
    <p class="label mb-3">{{ collect([$experience->employment_type, $experience->location])->filter()->implode(' · ') }}</p>
@endif
@if ($experience->summary)<p>{{ $experience->summary }}</p>@endif
@if (! empty($experience->achievements))
    <ul class="mt-3 list-disc space-y-1 pl-5">
        @foreach ($experience->achievements as $achievement)<li>{{ $achievement }}</li>@endforeach
    </ul>
@endif
