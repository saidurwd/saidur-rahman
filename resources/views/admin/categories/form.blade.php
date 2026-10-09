@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Categories' => route('admin.categories.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit category' : 'New category')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.categories.update', $record) : route('admin.categories.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-12"><x-admin.form.input name="name" label="Name" :value="$record->name" required data-slug-source="slug" /></div>
                    <div class="col-md-12"><x-admin.form.input name="slug" label="Slug" :value="$record->slug" help="Leave blank to generate it from the name. Changing it later adds a 301 redirect." /></div>
                </div>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ $editing ? 'Save changes' : 'Create' }}</button>

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
        <x-admin.delete-button :action="route('admin.categories.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
