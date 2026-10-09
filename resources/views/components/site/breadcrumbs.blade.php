@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->class('label no-print') }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="{{ route('home') }}" class="no-underline hover:text-accent">Home</a></li>
        @foreach ($items as $label => $url)
            <li aria-hidden="true">/</li>
            <li @if (! $url) aria-current="page" @endif>
                @if ($url)<a href="{{ $url }}" class="no-underline hover:text-accent">{{ $label }}</a>@else<span class="text-fg">{{ $label }}</span>@endif
            </li>
        @endforeach
    </ol>
</nav>
