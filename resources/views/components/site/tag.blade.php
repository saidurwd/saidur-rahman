@props(['accent' => false])

<span {{ $attributes->class(['tag', 'tag-accent' => $accent]) }}>{{ $slot }}</span>
