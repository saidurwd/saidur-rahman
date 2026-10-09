@props(['media', 'sizes' => '(min-width: 64rem) 66vw, 100vw', 'eager' => false, 'alt' => null])

{{-- Responsive image from a medialibrary item: WebP thumb (480w) and large (1400w) with reserved space. --}}
@php
    $thumb = $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null;
    $large = $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $media->getUrl();
    $width = $media->getCustomProperty('width');
    $height = $media->getCustomProperty('height');
    $alt = $alt ?? (string) $media->getCustomProperty('alt');
@endphp

<img src="{{ $large }}"
     @if ($thumb) srcset="{{ $thumb }} 480w, {{ $large }} 1400w" sizes="{{ $sizes }}" @endif
     alt="{{ $alt }}"
     @if ($width && $height) width="{{ min($width, 1400) }}" height="{{ (int) round(min($width, 1400) * $height / $width) }}" @endif
     @if ($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif
     {{ $attributes->class('h-auto w-full') }}>
