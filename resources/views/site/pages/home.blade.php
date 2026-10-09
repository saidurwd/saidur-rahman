@extends('site.layouts.app')

@section('content')
    <section class="blueprint">
        <div class="wrap py-24">
            <p class="eyebrow mb-6">Placeholder</p>
            <h1 class="text-display text-fg">{{ app(\App\Services\SettingService::class)->get('site_name', config('app.name')) }}</h1>
            <p class="mt-6 max-w-xl text-lg text-muted">The home page is built in Phase 5.</p>
        </div>
    </section>
@endsection
