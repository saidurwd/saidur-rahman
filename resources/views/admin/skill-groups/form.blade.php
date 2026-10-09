@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Skill groups' => route('admin.skill-groups.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit skill group' : 'New skill group')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.skill-groups.update', $record) : route('admin.skill-groups.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-12"><x-admin.form.input name="name" label="Name" :value="$record->name" required /></div>
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
        <x-admin.delete-button :action="route('admin.skill-groups.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
