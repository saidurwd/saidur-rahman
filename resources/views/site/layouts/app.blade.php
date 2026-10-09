<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0F1B2D">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    {{-- Set the theme and the js flag before first paint so there is no flash. --}}
    <script>
        (function () {
            var d = document.documentElement, t = null;
            try { t = localStorage.getItem('theme'); } catch (e) {}
            if (t !== 'light' && t !== 'dark') { t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
            d.dataset.theme = t;
            d.classList.add('js');
        })();
    </script>

    @hasSection('seo')
        @yield('seo')
    @else
        <x-site.seo :title="trim($__env->yieldContent('title')) ?: null" :description="trim($__env->yieldContent('description')) ?: null" />
    @endif

    @php
        try {
            $headingFont = Vite::asset('node_modules/@fontsource-variable/fraunces/files/fraunces-latin-wght-normal.woff2');
        } catch (\Throwable) {
            $headingFont = null;
        }
    @endphp
    @if ($headingFont)
        <link rel="preload" href="{{ $headingFont }}" as="font" type="font/woff2" crossorigin>
    @endif

    <link rel="alternate" type="application/rss+xml" title="{{ $siteName }} — Articles" href="{{ url('/feed') }}">

    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @stack('head')
</head>
<body class="min-h-dvh flex flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded focus:bg-accent focus:px-4 focus:py-2 focus:text-accent-fg">Skip to content</a>

<header data-site-header x-data="siteHeader" @scroll.window.passive="onScroll()" @keydown.escape.window="close()"
    class="sticky top-0 z-40 border-b border-transparent bg-bg/85 backdrop-blur transition-[padding,border-color] duration-300 motion-reduce:transition-none"
    :class="scrolled ? 'border-line py-2' : 'py-4'">
    <div class="wrap flex items-center justify-between gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3 text-fg no-underline" aria-label="{{ $siteName }}, home">
            <x-site.monogram class="size-8" />
            <span class="font-serif text-lg font-medium tracking-tight">{{ $siteName }}</span>
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
            @foreach ($headerMenu as $item)
                @php $active = rtrim(parse_url($item['href'], PHP_URL_PATH) ?? '', '/') === rtrim(request()->getPathInfo(), '/'); @endphp
                <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif
                    class="rounded px-3 py-2 text-[0.95rem] no-underline transition-colors {{ $active ? 'text-accent' : 'text-fg hover:text-accent' }}">{{ $item['label'] }}</a>
            @endforeach
            <x-site.theme-toggle class="ml-2" />
        </nav>

        <div class="flex items-center gap-1 md:hidden">
            <x-site.theme-toggle />
            <button type="button" class="inline-flex size-9 items-center justify-center rounded text-fg" @click="open = !open"
                :aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <svg x-show="!open" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-menu" x-show="open" x-cloak class="wrap mt-3 border-t border-line pt-3 md:hidden" aria-label="Mobile">
        <ul class="flex flex-col">
            @foreach ($headerMenu as $item)
                <li><a href="{{ $item['href'] }}" @click="close()" class="block py-3 text-lg text-fg no-underline">{{ $item['label'] }}</a></li>
            @endforeach
        </ul>
    </nav>
</header>

<main id="main" class="flex-1">
    @yield('content')
</main>

<footer data-site-footer class="mt-24 border-t border-line">
    <div class="wrap grid gap-10 py-14 md:grid-cols-3">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-fg no-underline">
                <x-site.monogram class="size-8" />
                <span class="font-serif text-lg">{{ $siteName }}</span>
            </a>
            @if ($availability)
                <p class="mt-5 inline-flex items-center gap-2 rounded-full border border-line px-3 py-1 text-sm text-muted">
                    <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>{{ $availability }}
                </p>
            @endif
        </div>

        <nav aria-label="Footer">
            <p class="label mb-4">Explore</p>
            <ul class="space-y-2">
                @foreach ([...$headerMenu, ...$footerMenu] as $item)
                    <li><a href="{{ $item['href'] }}" class="text-fg no-underline hover:text-accent">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div>
            <p class="label mb-4">Elsewhere</p>
            <ul class="space-y-2">
                @foreach ($socialLinks as $label => $url)
                    <li><a href="{{ $url }}" rel="me noopener" target="_blank" class="text-fg no-underline hover:text-accent">{{ $label }}</a></li>
                @endforeach
                <li><a href="{{ url('/feed') }}" class="text-fg no-underline hover:text-accent">RSS feed</a></li>
                @if ($contactEmail)
                    <li><a href="mailto:{{ $contactEmail }}" class="text-fg no-underline hover:text-accent">{{ $contactEmail }}</a></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="border-t border-line">
        <p class="wrap label py-5">© {{ date('Y') }} {{ $siteName }}</p>
    </div>
</footer>
</body>
</html>
