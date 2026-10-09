@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'schema' => [],
    'publishedAt' => null,
])

@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = $settings->get('site_name', config('app.name'));

    $fullTitle = $title ? "{$title} · {$siteName}" : $settings->get('seo_title', $siteName);
    $description = $description ?: $settings->get('seo_description');
    $canonical = $canonical ?: url()->current();
    $image = app(\App\Services\ImageStore::class)->url($image ?: $settings->get('og_image'));
    $schemas = array_is_list($schema) ? $schema : [$schema];
    $schemas = array_values(array_filter($schemas));
@endphp

<title>{{ $fullTitle }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?: $fullTitle }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
@endif
@if ($type === 'article' && $publishedAt)
    <meta property="article:published_time" content="{{ $publishedAt }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title ?: $fullTitle }}">
@if ($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if ($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif

@foreach ($schemas as $item)
    <script type="application/ld+json">{!! json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
{{ $slot }}
