@extends('site.layouts.app')

@php $settings = app(\App\Services\SettingService::class); @endphp

@section('seo')
    <x-site.seo title="Contact" description="Get in touch about senior IT roles, ERP advisory or a project." :schema="$schema" />
@endsection

@push('head')
    @if ($turnstileKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

@section('content')
<div class="wrap py-14 md:py-20">
    <x-site.breadcrumbs :items="['Contact' => null]" />

    <div class="mt-8 grid gap-14 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
            <x-site.section-heading :level="1" eyebrow="Contact" title="Let’s talk">
                {{ $availability ?: 'Send a message about a role, an advisory project or a question about my work.' }}
            </x-site.section-heading>

            @if (session('contact_sent'))
                <div class="mt-10 rounded border border-accent bg-surface p-6" role="status" data-reveal>
                    <h2 class="font-serif text-h3">Message sent</h2>
                    <p class="mt-2 text-muted">Thank you. I read everything that comes in and will reply personally, usually within a few working days.</p>
                    <x-site.button :href="route('home')" variant="outline" class="mt-5">Back to the home page</x-site.button>
                </div>
            @else
                <form method="POST" action="{{ route('contact.store') }}" class="mt-10 space-y-6" novalidate>
                    @csrf
                    <input type="hidden" name="form_token" value="{{ $formToken }}">

                    {{-- Honeypot: hidden from people and from assistive tech, tempting to bots. --}}
                    <div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    @if ($errors->any())
                        <div class="rounded border border-line bg-surface p-4" role="alert" tabindex="-1" autofocus>
                            <p class="font-medium">Please check the highlighted fields.</p>
                            @error('form_token')<p class="mt-1 text-sm text-muted">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        @include('site.contact.field', ['name' => 'name', 'label' => 'Your name', 'required' => true, 'autocomplete' => 'name'])
                        @include('site.contact.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'])
                        @include('site.contact.field', ['name' => 'company', 'label' => 'Company or organisation', 'autocomplete' => 'organization'])
                        @include('site.contact.field', ['name' => 'subject', 'label' => 'Subject'])
                    </div>

                    <div>
                        <label for="message" class="mb-2 block font-medium">Message <span class="text-accent" aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" rows="7" required minlength="10" maxlength="5000"
                            aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" @if ($errors->has('message')) aria-describedby="message-error" @endif
                            class="w-full rounded border bg-surface px-3 py-2 text-fg {{ $errors->has('message') ? 'border-red-600' : 'border-line' }}">{{ old('message') }}</textarea>
                        @error('message')<p id="message-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    @if ($turnstileKey)
                        <div>
                            <div class="cf-turnstile" data-sitekey="{{ $turnstileKey }}"></div>
                            @error('cf-turnstile-response')<p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-4">
                        <button type="submit" class="btn btn-primary">Send message</button>
                        <p class="text-sm text-muted">Your details are used only to reply to you.</p>
                    </div>
                </form>
            @endif
        </div>

        <aside class="lg:pt-20">
            <dl class="space-y-6 border-t border-line pt-6">
                @if ($contactEmail)
                    <div><dt class="label">Email</dt><dd class="mt-1"><a href="mailto:{{ $contactEmail }}" class="link">{{ $contactEmail }}</a></dd></div>
                @endif
                @if ($settings->get('location'))
                    <div><dt class="label">Based in</dt><dd class="mt-1">{{ $settings->get('location') }}</dd></div>
                @endif
                @foreach ($socialLinks as $label => $url)
                    <div><dt class="label">{{ $label }}</dt><dd class="mt-1"><a href="{{ $url }}" class="link" rel="me noopener" target="_blank">{{ $url }}</a></dd></div>
                @endforeach
            </dl>
        </aside>
    </div>
</div>
@endsection
