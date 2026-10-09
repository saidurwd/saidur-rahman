@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Pages' => route('admin.pages.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit page' : 'New page')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.pages.update', $record) : route('admin.pages.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-12"><x-admin.form.input name="title" label="Title" :value="$record->title" required data-slug-source="slug" /></div>
                    <div class="col-md-12"><x-admin.form.input name="slug" label="Slug" :value="$record->slug" help="Leave blank to generate it from the title. Changing it later adds a 301 redirect." /></div>
                    <div class="col-md-12"><x-admin.form.editor name="body" label="Content" :value="$record->body" /></div>
                </div>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.toggle name="is_published" label="Published" :checked="$record->is_published" />
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ $editing ? 'Save changes' : 'Create' }}</button>
                    @if ($editing && $record->is_published)
                        <a href="{{ url($record->publicPath()) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">View on site</a>
                    @endif
                </div>
                @if ($editing)
                    <p class="small text-body-secondary mt-3 mb-0">Last saved {{ $record->updated_at->diffForHumans() }}.</p>
                @endif
            </x-admin.card>
            <x-admin.card title="Search and sharing">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$record->seo_title" help="Up to 70 characters." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$record->seo_description" :rows="3" help="Up to 170 characters." />
            </x-admin.card>
        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4">
        <x-admin.delete-button :action="route('admin.pages.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
