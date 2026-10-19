@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Expertise' => route('admin.expertise.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit expertise' : 'New expertise')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.expertise.update', $record) : route('admin.expertise.store') }}">
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
                    <div class="col-md-6"><x-admin.form.input name="icon" label="Icon name" :value="$record->icon" help="Optional, for example a Bootstrap icon name." /></div>
                    <div class="col-md-12"><x-admin.form.textarea name="summary" label="Summary" :value="$record->summary" :rows="3" help="Plain text." /></div>
                    <div class="col-md-12"><x-admin.form.editor name="body" label="Details" :value="$record->body" /></div>
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

        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4">
        <x-admin.delete-button :action="route('admin.expertise.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
