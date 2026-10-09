@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Education' => route('admin.educations.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit education entry' : 'New education entry')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.educations.update', $record) : route('admin.educations.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-12"><x-admin.form.input name="institution" label="Institution" :value="$record->institution" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="degree" label="Degree" :value="$record->degree" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="field" label="Field of study" :value="$record->field" /></div>
                    <div class="col-md-6"><x-admin.form.input name="start_year" type="number" label="Start year" :value="$record->start_year" /></div>
                    <div class="col-md-6"><x-admin.form.input name="end_year" type="number" label="End year" :value="$record->end_year" /></div>
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
        <x-admin.delete-button :action="route('admin.educations.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
