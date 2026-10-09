@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Experiences' => route('admin.experiences.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit experience' : 'New experience')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.experiences.update', $record) : route('admin.experiences.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-6"><x-admin.form.input name="company" label="Company" :value="$record->company" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="role" label="Role" :value="$record->role" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="location" label="Location" :value="$record->location" /></div>
                    <div class="col-md-6"><x-admin.form.select name="employment_type" label="Employment type" :options="['Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Contract' => 'Contract', 'Consulting' => 'Consulting', 'Freelance' => 'Freelance']" :value="$record->employment_type" placeholder="Choose…" /></div>
                    <div class="col-md-6"><x-admin.form.input name="start_date" type="date" label="Start date" :value="optional($record->start_date)->format('Y-m-d')" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="end_date" type="date" label="End date" :value="optional($record->end_date)->format('Y-m-d')" help="Ignored for the current role." /></div>
                    <div class="col-md-12"><x-admin.form.toggle name="is_current" label="This is my current role" :checked="(bool) $record->is_current" /></div>
                    <div class="col-md-12"><x-admin.form.textarea name="summary" label="Summary" :value="$record->summary" :rows="3" /></div>
                    <div class="col-md-12"><x-admin.form.textarea name="achievements" label="Achievements" :value="implode("\n", (array) old('achievements', $record->achievements ?? []))" :rows="6" help="One per line." /></div>
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
        <x-admin.delete-button :action="route('admin.experiences.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
