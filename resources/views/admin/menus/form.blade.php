@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Menus' => route('admin.menus.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit menu item' : 'New menu item')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.menus.update', $record) : route('admin.menus.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-6"><x-admin.form.input name="label" label="Label" :value="$record->label" required /></div>
                    <div class="col-md-6"><x-admin.form.select name="location" label="Location" :options="['header' => 'Header', 'footer' => 'Footer']" :value="$record->location" placeholder="Choose…" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="url" label="URL" :value="$record->url" help="A path such as /projects, a full https:// address, or mailto:." /></div>
                    <div class="col-md-6"><x-admin.form.input name="route" label="Route name" :value="$record->route" help="Optional. A Laravel route name (for example home), used instead of the URL." /></div>
                </div>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.toggle name="is_published" label="Published" :checked="$record->is_published" />
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
        <x-admin.delete-button :action="route('admin.menus.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
