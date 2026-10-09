@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Skills' => route('admin.skills.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit skill' : 'New skill')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.skills.update', $record) : route('admin.skills.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="row">
                    <div class="col-md-12"><x-admin.form.select name="skill_group_id" label="Group" :options="$groups" :value="$record->skill_group_id" placeholder="Choose…" required /></div>
                    <div class="col-md-12"><x-admin.form.input name="name" label="Name" :value="$record->name" required /></div>
                    <div class="col-md-6"><x-admin.form.select name="level" label="Level" :options="[1 => '1 · Working knowledge', 2 => '2 · Competent', 3 => '3 · Proficient', 4 => '4 · Advanced', 5 => '5 · Expert']" :value="$record->level" placeholder="Choose…" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="years" type="number" label="Years of experience" :value="$record->years" /></div>
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
        <x-admin.delete-button :action="route('admin.skills.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
