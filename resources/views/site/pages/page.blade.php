@extends('site.layouts.app')

@section('seo')
    <x-site.seo :title="$page->seo_title ?: $page->title" :description="$page->seo_description" :schema="$schema" />
@endsection

@section('content')
<article class="wrap wrap-narrow py-14 md:py-20">
    <h1 class="text-h1">{{ $page->title }}</h1>
    @if ($page->body)<div class="prose-site mt-10">{!! $page->body !!}</div>@endif
</article>
@endsection
