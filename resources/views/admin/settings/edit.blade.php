@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Profile & settings' => null]])

@section('title', 'Profile & settings')

@section('content')
<form method="POST" enctype="multipart/form-data" data-unsaved-warning action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Profile">
                <div class="row">
                    <div class="col-md-6"><x-admin.form.input name="site_name" label="Name" :value="$values['site_name'] ?? ''" required help="Shown in the header, footer and page titles." /></div>
                    <div class="col-md-6"><x-admin.form.input name="headline" label="Headline" :value="$values['headline'] ?? ''" /></div>
                </div>
                <x-admin.form.input name="hero_title" label="Home page headline" :value="$values['hero_title'] ?? ''" />
                <x-admin.form.textarea name="positioning" label="Positioning line" :value="$values['positioning'] ?? ''" :rows="3" help="One or two sentences. Used on the home page and as a fallback description." />
                <x-admin.form.textarea name="bio" label="Bio" :value="$values['bio'] ?? ''" :rows="6" help="Your story. Shown on the About page." />
                <x-admin.form.textarea name="philosophy" label="Leadership philosophy" :value="$values['philosophy'] ?? ''" :rows="4" help="Shown on the About page." />
                <div class="row">
                    <div class="col-md-6"><x-admin.form.input name="location" label="Location" :value="$values['location'] ?? ''" /></div>
                    <div class="col-md-6"><x-admin.form.input name="contact_email" type="email" label="Contact email" :value="$values['contact_email'] ?? ''" help="Contact form messages are emailed here and published in the footer." /></div>
                </div>
                <x-admin.form.toggle name="contact_autoreply" label="Send senders an automatic confirmation email" :checked="$values['contact_autoreply'] ?? true" />
            </x-admin.card>

            <x-admin.card title="Numbers strip">
                <p class="small text-body-secondary">Up to four figures shown under the home page headline, for example <code>21+</code> / <em>Years in enterprise IT</em>. Only add numbers you can defend in an interview. Rows left blank are hidden.</p>
                @for ($i = 0; $i < 4; $i++)
                    <div class="row g-2 mb-2">
                        <div class="col-4"><input type="text" name="stats[{{ $i }}][value]" value="{{ old("stats.{$i}.value", $values['stats'][$i]['value'] ?? '') }}" class="form-control form-control-sm @error("stats.{$i}.value") is-invalid @enderror" placeholder="21+" aria-label="Figure {{ $i + 1 }} value"></div>
                        <div class="col-8"><input type="text" name="stats[{{ $i }}][label]" value="{{ old("stats.{$i}.label", $values['stats'][$i]['label'] ?? '') }}" class="form-control form-control-sm @error("stats.{$i}.label") is-invalid @enderror" placeholder="Years in enterprise IT" aria-label="Figure {{ $i + 1 }} label"></div>
                    </div>
                @endfor
            </x-admin.card>

            <x-admin.card title="Availability">
                <x-admin.form.toggle name="availability_open" label="Show the availability status" :checked="$values['availability_open'] ?? false" />
                <x-admin.form.input name="availability_text" label="Status text" :value="$values['availability_text'] ?? ''" help="For example: Open to senior IT leadership roles in Qatar and the GCC." />
            </x-admin.card>

            <x-admin.card title="Social links">
                <x-admin.form.input name="linkedin" type="url" label="LinkedIn" :value="$values['linkedin'] ?? ''" placeholder="https://www.linkedin.com/in/…" />
                <x-admin.form.input name="github" type="url" label="GitHub" :value="$values['github'] ?? ''" placeholder="https://github.com/…" />
            </x-admin.card>

            <x-admin.card title="Search and sharing defaults">
                <x-admin.form.input name="seo_title" label="Default SEO title" :value="$values['seo_title'] ?? ''" help="Up to 70 characters. Used on pages without their own." />
                <x-admin.form.textarea name="seo_description" label="Default SEO description" :value="$values['seo_description'] ?? ''" :rows="3" help="Up to 170 characters." />
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Save">
                <button type="submit" class="btn btn-primary w-100">Save settings</button>
            </x-admin.card>

            <x-admin.card title="Portrait">
                <x-admin.form.image-upload name="profile_photo" label="Professional photo" :current="$profilePhotoUrl" help="Converted to WebP; camera data such as location is removed." />
                @if ($profilePhotoUrl)<x-admin.form.toggle name="remove_profile_photo" label="Remove the photo" />@endif
            </x-admin.card>

            <x-admin.card title="Share image">
                <x-admin.form.image-upload name="og_image" label="Open Graph image" :current="$ogImageUrl" help="1200×630 works best. Shown when links are shared." />
                @if ($ogImageUrl)<x-admin.form.toggle name="remove_og_image" label="Remove the image" />@endif
            </x-admin.card>
        </div>
    </div>
</form>
@endsection
