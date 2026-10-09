@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Testimonials' => route('admin.testimonials.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit testimonial' : 'New testimonial')

@section('content')
<form method="POST" data-unsaved-warning enctype="multipart/form-data"
      action="{{ $editing ? route('admin.testimonials.update', $record) : route('admin.testimonials.store') }}">
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
                    <div class="col-md-6"><x-admin.form.input name="position" label="Position" :value="$record->position" /></div>
                    <div class="col-md-6"><x-admin.form.input name="company" label="Company" :value="$record->company" /></div>
                    <div class="col-md-12"><x-admin.form.textarea name="quote" label="Quote" :value="$record->quote" :rows="5" /></div>
                    <div class="col-md-12"><x-admin.form.image-upload name="photo" label="Photo" :current="$record->getFirstMediaUrl('photo', 'thumb') ?: null" /></div>
                    <div class="col-md-12">@if ($record->getFirstMedia('photo'))<x-admin.form.toggle name="remove_photo" label="Remove the photo" />@endif</div>
                </div>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publish">
                <x-admin.form.toggle name="is_approved" label="Approved (shown on the site)" :checked="$record->is_approved" />
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
        <x-admin.delete-button :action="route('admin.testimonials.destroy', $record)" label="Move to Trash" />
    </div>
@endif
@endsection
