@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="CV" description="Download my CV: IT leadership and engineering versions." :schema="$schema" />
@endsection

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['CV' => null]" />

    <div class="mt-8">
        <x-site.section-heading :level="1" eyebrow="Curriculum vitae" title="Download my CV">
            {{ $cvs->count() > 1 ? 'Choose the version that fits what you are looking for.' : 'The latest version, as a PDF.' }}
        </x-site.section-heading>
    </div>

    @if ($cvs->isEmpty())
        <p class="mt-12 text-muted">My CV is not available to download yet. <a href="{{ route('contact') }}" class="link">Get in touch</a> and I will send it to you.</p>
    @else
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @foreach ($cvs as $cv)
                <div class="card flex flex-col" data-reveal>
                    <p class="label mb-3">PDF · {{ $cv->sizeForHumans() }} · updated {{ $cv->updated_at->format('M Y') }}</p>
                    <h2 class="font-serif text-h3">{{ $cv->label }}</h2>
                    <div class="mt-auto pt-6">
                        <x-site.button :href="route('cv.download', $cv->id)" rel="nofollow">Download {{ $cv->label }}</x-site.button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
