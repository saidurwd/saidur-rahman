@php
    $editing = $record->exists;
    $publishedAt = old('published_at', $record->published_at?->format('Y-m-d\TH:i'));
    $link = old('youtube_url', $record->youtube_id ? $record->watchUrl() : null);
@endphp

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Videos' => route('admin.videos.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit video' : 'Add video')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.videos.update', $record) : route('admin.videos.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <x-admin.form.input name="youtube_url" type="text" label="YouTube link" :value="$link" required placeholder="https://www.youtube.com/watch?v=…"
                    help="Paste any YouTube link: watch, youtu.be, shorts or embed. Only the video ID is stored." />
                @if ($editing)
                    <div class="mb-3">
                        <img src="{{ $record->thumbnailUrl('mqdefault') }}" alt="Thumbnail of {{ $record->title }}" width="320" height="180" class="rounded border">
                    </div>
                @endif
                <x-admin.form.input name="title" label="Title" :value="$record->title" required data-slug-source="slug" />
                <x-admin.form.input name="slug" label="Slug" :value="$record->slug" help="URL: {{ url('/videos') }}/…  Leave blank to generate it from the title. Changing it later adds a 301 redirect from the old address." />
                <x-admin.form.textarea name="description" label="Description" :value="$record->description" :rows="6" help="Plain text, up to 5,000 characters. Shown under the video and searched by visitors." />
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.toggle name="is_published" label="Published" :checked="$record->is_published" />
                <x-admin.form.toggle name="is_featured" label="Featured at the top of the videos page" :checked="$record->is_featured" />
                <x-admin.form.input name="published_at" type="datetime-local" label="Publish date" :value="$publishedAt" help="Leave blank to use the moment it is first published. Videos are listed newest first." />
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ $editing ? 'Save changes' : 'Add video' }}</button>
                    @if ($editing && $record->is_published)
                        <a href="{{ url($record->publicPath()) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">View on site</a>
                    @endif
                </div>
                @if ($editing)
                    <p class="small text-body-secondary mt-3 mb-0">Last saved {{ $record->updated_at->diffForHumans() }}.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Organise">
                <x-admin.form.input name="topic" label="Topic" :value="$record->topic" list="known-topics" help="Groups videos into filter buttons on the site, e.g. Laravel, ERP, Leadership." />
                <datalist id="known-topics">@foreach ($topics as $topic)<option value="{{ $topic }}">@endforeach</datalist>
            </x-admin.card>

            <x-admin.card title="Search and sharing">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$record->seo_title" help="Up to 70 characters. Defaults to the video title." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$record->seo_description" :rows="3" help="Up to 170 characters. Defaults to the description." />
            </x-admin.card>
        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4">
        <x-admin.delete-button :action="route('admin.videos.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
