@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'CV files' => route('admin.cv-files.index'), ($editing ? 'Edit' : 'Upload') => null]])

@section('title', $editing ? 'Edit CV' : 'Upload CV')

@section('content')
<form method="POST" enctype="multipart/form-data" data-unsaved-warning action="{{ $editing ? route('admin.cv-files.update', $record) : route('admin.cv-files.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <x-admin.form.input name="label" label="Label" :value="$record->label" required help="For example IT Leadership or Engineering. Visitors choose between labels." list="cv-labels" />
                <datalist id="cv-labels"><option value="IT Leadership"><option value="Engineering"></datalist>

                <div class="mb-3">
                    <label for="file" class="form-label">PDF file @unless ($editing)<span class="text-danger">*</span>@endunless</label>
                    <input type="file" id="file" name="file" accept="application/pdf" class="form-control @error('file') is-invalid @enderror">
                    @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">
                        PDF, up to 10 MB. Stored privately under a random name.
                        @if ($editing) Leave empty to keep the current file ({{ $record->sizeForHumans() }}). @endif
                    </div>
                </div>

                <x-admin.form.toggle name="is_active" label="Active" :checked="$record->is_active" help="Making this active turns off other versions with the same label." />

                @if ($editing)
                    <p class="small text-body-secondary">Downloaded {{ number_format($record->downloads) }} {{ \Illuminate\Support\Str::plural('time', $record->downloads) }}.</p>
                @endif
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Upload' }}</button>
            </x-admin.card>
        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4"><x-admin.delete-button :action="route('admin.cv-files.destroy', $record)" label="Move to Trash" /></div>
@endif
@endsection
