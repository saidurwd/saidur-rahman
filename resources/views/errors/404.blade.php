@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Page not found" :noindex="true" />
@endsection

@section('content')
<section class="blueprint">
    <div class="wrap py-24 md:py-32">
        <p class="eyebrow mb-6">Error 404</p>
        <h1 class="text-display text-fg">This page is not on the map.</h1>
        <p class="mt-6 max-w-xl text-lg text-muted">The address may have changed or the page may have been removed. Try one of these instead.</p>
        <div class="mt-9 flex flex-wrap gap-4">
            <x-site.button :href="route('home')">Home</x-site.button>
            <x-site.button :href="route('projects.index')" variant="outline">Projects</x-site.button>
        </div>
    </div>
</section>
@endsection
