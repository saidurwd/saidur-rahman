@php
    $editing = $record->exists;
    $cover = $editing ? $record->getFirstMedia('cover') : null;
    $statusOptions = collect(\App\Enums\PostStatus::cases())->mapWithKeys(fn ($s) => [$s->value => \Illuminate\Support\Str::headline($s->value)])->all();
    $publishedAt = old('published_at', $record->published_at?->format('Y-m-d\TH:i'));
    $tagList = old('tags') !== null ? (is_array(old('tags')) ? old('tags') : explode(',', old('tags'))) : $record->tags->pluck('name')->all();
@endphp

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Articles' => route('admin.posts.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit article' : 'New article')

@section('content')
<form method="POST" enctype="multipart/form-data" data-unsaved-warning
      action="{{ $editing ? route('admin.posts.update', $record) : route('admin.posts.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <x-admin.form.input name="title" label="Title" :value="$record->title" required data-slug-source="slug" />
                <x-admin.form.input name="slug" label="Slug" :value="$record->slug" help="URL: {{ url('/blog') }}/…  Leave blank to generate it from the title. Changing it later adds a 301 redirect." />
                <x-admin.form.textarea name="excerpt" label="Excerpt" :value="$record->excerpt" :rows="3" help="Plain text, up to 600 characters. Shown on cards, in search results and in the RSS feed." />
                <x-admin.form.editor name="body" label="Article" :value="$record->body" :rows="20" help="Use Heading 2 and Heading 3 for sections: they become the table of contents." />
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.select name="status" label="Status" :options="$statusOptions" :value="$record->status" required />
                <x-admin.form.input name="published_at" type="datetime-local" label="Publish date" :value="$publishedAt"
                    help="Scheduled articles go live at this time ({{ config('app.timezone') }}). Leave empty to publish now." />
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ $editing ? 'Save changes' : 'Create article' }}</button>
                    @if ($editing && $record->status === \App\Enums\PostStatus::Published)
                        <a href="{{ url($record->publicPath()) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">View on site</a>
                    @endif
                </div>
                @if ($editing)
                    <p class="small text-body-secondary mt-3 mb-0">{{ number_format($record->views) }} {{ \Illuminate\Support\Str::plural('view', $record->views) }} · {{ $record->reading_minutes }} min read · saved {{ $record->updated_at->diffForHumans() }}.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Organise">
                <x-admin.form.select name="category_id" label="Category" :options="$categories" :value="$record->category_id" placeholder="No category" />
                <x-admin.form.input name="tags" label="Tags" :value="implode(', ', $tagList)" list="known-tags" help="Comma separated. New tags are created automatically." />
                <datalist id="known-tags">@foreach ($knownTags as $tag)<option value="{{ $tag }}">@endforeach</datalist>
            </x-admin.card>

            <x-admin.card title="Cover image">
                <x-admin.form.image-upload name="cover" label="Cover" :current="$cover?->getUrl('thumb') ?: $cover?->getUrl()" alt-name="cover_alt" :alt-value="$cover?->getCustomProperty('alt')" help="Also used when the article is shared." />
                @if ($cover)<x-admin.form.toggle name="remove_cover" label="Remove the cover image" />@endif
            </x-admin.card>

            <x-admin.card title="Search and sharing">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$record->seo_title" help="Up to 70 characters. Defaults to the title." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$record->seo_description" :rows="3" help="Up to 170 characters. Defaults to the excerpt." />
            </x-admin.card>
        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4"><x-admin.delete-button :action="route('admin.posts.destroy', $record)" label="Move to Trash" /></div>
@endif
@endsection
