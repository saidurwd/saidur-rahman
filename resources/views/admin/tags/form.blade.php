@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Articles' => route('admin.posts.index'), 'Tags' => route('admin.tags.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit tag' : 'New tag')

@section('content')
<form method="POST" data-unsaved-warning action="{{ $editing ? route('admin.tags.update', $record) : route('admin.tags.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="row"><div class="col-lg-8">
        <x-admin.card>
            <x-admin.form.input name="name" label="Name" :value="$record->name" required data-slug-source="slug" />
            <x-admin.form.input name="slug" label="Slug" :value="$record->slug" help="Leave blank to generate it from the name." />
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create tag' }}</button>
        </x-admin.card>
    </div></div>
</form>

@if ($editing)
    <div class="mb-4"><x-admin.delete-button :action="route('admin.tags.destroy', $record)" label="Delete tag" confirm="Delete this tag? It will be removed from its articles." /></div>
@endif
@endsection
