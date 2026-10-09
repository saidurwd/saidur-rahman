@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$service->title" :description="$service->summary" :schema="$schema" />
@endsection

@section('content')
<article class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Services' => route('services.index'), $service->title => null]" />
    <div class="mt-8"><x-site.section-heading :level="1" eyebrow="Service" :title="$service->title">{{ $service->summary }}</x-site.section-heading></div>
    @if ($service->body)<div class="prose-site mt-10">{!! $service->body !!}</div>@endif
</article>
@endsection
