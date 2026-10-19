@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$expertise->title" :description="$expertise->summary" :schema="$schema" />
@endsection

@section('content')
<article class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Expertise' => route('expertise.index'), $expertise->title => null]" />
    <div class="mt-8"><x-site.section-heading :level="1" eyebrow="Expertise" :title="$expertise->title">{{ $expertise->summary }}</x-site.section-heading></div>
    @if ($expertise->body)<div class="prose-site mt-10">{!! $expertise->body !!}</div>@endif
</article>
@endsection
