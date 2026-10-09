@props(['title' => null, 'footer' => null, 'flush' => false])

<div {{ $attributes->class(['card', 'mb-4']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            @isset($actions)
                <div class="card-tools">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['card-body', 'p-0' => $flush])>{{ $slot }}</div>
    @if ($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endif
</div>
