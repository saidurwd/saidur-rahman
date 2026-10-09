@php
    $editing = $project->exists;
    $cover = $editing ? $project->getFirstMedia('cover') : null;
    $gallery = $editing ? $project->getMedia('gallery') : collect();
@endphp

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Projects' => route('admin.projects.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit project' : 'New project')

@section('content')
<form method="POST" enctype="multipart/form-data" data-unsaved-warning
      action="{{ $editing ? route('admin.projects.update', $project) : route('admin.projects.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        {{-- Content --}}
        <div class="col-lg-8">
            <x-admin.card>
                <x-admin.form.input name="title" label="Title" :value="$project->title" required data-slug-source="slug" />
                <x-admin.form.input name="slug" label="Slug" :value="$project->slug" help="URL: {{ url('/projects') }}/…  Leave blank to generate it from the title. Changing it later adds a 301 redirect from the old address." />
                <x-admin.form.textarea name="summary" label="Summary" :value="$project->summary" :rows="3" help="Plain text, up to 600 characters. Shown on cards and in search results." />
                <x-admin.form.editor name="problem" label="Context and challenge" :value="$project->problem" />
                <x-admin.form.editor name="solution" label="My role and solution" :value="$project->solution" />
                <x-admin.form.editor name="architecture" label="Architecture" :value="$project->architecture" />
                <x-admin.form.editor name="outcomes" label="Outcomes" :value="$project->outcomes" help="Only add figures you can defend in an interview." />
            </x-admin.card>

            <x-admin.card title="Details">
                <div class="row">
                    <div class="col-md-6"><x-admin.form.input name="client" label="Client" :value="$project->client" /></div>
                    <div class="col-md-6"><x-admin.form.input name="role" label="My role" :value="$project->role" /></div>
                    <div class="col-md-6"><x-admin.form.input name="period" label="Period" :value="$project->period" placeholder="2022 – 2024" /></div>
                    <div class="col-md-6"><x-admin.form.input name="tech_stack" label="Tech stack" :value="implode(', ', old('tech_stack', $project->tech_stack ?? []))" help="Comma separated." /></div>
                    <div class="col-md-6"><x-admin.form.input name="live_url" type="url" label="Live URL" :value="$project->live_url" /></div>
                    <div class="col-md-6"><x-admin.form.input name="repo_url" type="url" label="Repository URL" :value="$project->repo_url" /></div>
                </div>
            </x-admin.card>
        </div>

        {{-- Publish box --}}
        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.select name="status" label="Status" :options="collect(\App\Enums\ProjectStatus::cases())->mapWithKeys(fn ($s) => [$s->value => \Illuminate\Support\Str::headline($s->value)])->all()" :value="$project->status" required />
                <x-admin.form.toggle name="is_featured" label="Featured on the home page" :checked="$project->is_featured" />
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ $editing ? 'Save changes' : 'Create project' }}</button>
                    @if ($editing && $project->status === \App\Enums\ProjectStatus::Published)
                        <a href="{{ url($project->publicPath()) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">View on site</a>
                    @endif
                </div>
                @if ($editing)
                    <p class="small text-body-secondary mt-3 mb-0">Last saved {{ $project->updated_at->diffForHumans() }}.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Search and sharing">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$project->seo_title" help="Up to 70 characters. Defaults to the project title." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$project->seo_description" :rows="3" help="Up to 170 characters. Defaults to the summary." />
            </x-admin.card>

            <x-admin.card title="Featured image">
                <x-admin.form.image-upload name="cover" label="Cover" :current="$cover?->getUrl('thumb') ?: $cover?->getUrl()" alt-name="cover_alt" :alt-value="$cover?->getCustomProperty('alt')" />
                @if ($cover)
                    <x-admin.form.toggle name="remove_cover" label="Remove the cover image" />
                @endif
            </x-admin.card>

            <x-admin.card title="Gallery">
                @foreach ($gallery as $image)
                    <div class="d-flex gap-2 align-items-start mb-3">
                        <img src="{{ $image->getUrl('thumb') ?: $image->getUrl() }}" alt="" width="72" height="54" class="rounded object-fit-cover flex-none">
                        <div class="flex-grow-1">
                            <input type="text" name="media_alt[{{ $image->id }}]" value="{{ old("media_alt.{$image->id}", $image->getCustomProperty('alt')) }}" class="form-control form-control-sm" placeholder="Alt text" aria-label="Alt text for image {{ $loop->iteration }}">
                            <div class="form-check mt-1">
                                <input type="checkbox" class="form-check-input" name="remove_media[]" value="{{ $image->id }}" id="rm{{ $image->id }}">
                                <label class="form-check-label small" for="rm{{ $image->id }}">Remove</label>
                            </div>
                        </div>
                    </div>
                @endforeach
                <label for="gallery" class="form-label">Add images</label>
                <input type="file" id="gallery" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control @error('gallery.*') is-invalid @enderror">
                @error('gallery.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Architecture diagrams and screenshots. Up to 5 MB each.</div>
            </x-admin.card>

        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4">
        <x-admin.delete-button :action="route('admin.projects.destroy', $project)" label="Move to Trash" />
    </div>
@endif
@endsection
