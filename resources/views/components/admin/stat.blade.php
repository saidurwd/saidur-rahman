@props(['label', 'value', 'icon' => 'bar-chart', 'href' => null, 'tone' => 'primary'])

<div class="info-box">
    <span class="info-box-icon text-bg-{{ $tone }} shadow-sm"><i class="bi bi-{{ $icon }}"></i></span>
    <div class="info-box-content">
        <span class="info-box-text">
            @if ($href)<a href="{{ $href }}" class="text-reset text-decoration-none">{{ $label }}</a>@else{{ $label }}@endif
        </span>
        <span class="info-box-number">{{ $value }}</span>
    </div>
</div>
