@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Certifications' => route('admin.certifications.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit certification' : 'New certification')

@section('content')
<form method="POST" data-unsaved-warning
      action="{{ $editing ? route('admin.certifications.update', $record) : route('admin.certifications.store') }}">
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
                    <div class="col-md-12"><x-admin.form.input name="issuer" label="Issuer" :value="$record->issuer" required /></div>
                    <div class="col-md-6"><x-admin.form.input name="issued_at" type="date" label="Issued" :value="optional($record->issued_at)->format('Y-m-d')" /></div>
                    <div class="col-md-6"><x-admin.form.input name="expires_at" type="date" label="Expires" :value="optional($record->expires_at)->format('Y-m-d')" help="Leave blank if it does not expire." /></div>
                    <div class="col-md-12"><x-admin.form.input name="credential_url" type="url" label="Credential URL" :value="$record->credential_url" /></div>
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
        <x-admin.delete-button :action="route('admin.certifications.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
