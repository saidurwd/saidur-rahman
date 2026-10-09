@php($editing = $record->exists)

@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Redirects' => route('admin.redirects.index'), ($editing ? 'Edit' : 'New') => null]])

@section('title', $editing ? 'Edit redirect' : 'New redirect')

@section('content')
<form method="POST" data-unsaved-warning action="{{ $editing ? route('admin.redirects.update', $record) : route('admin.redirects.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card>
                <x-admin.form.input name="from_path" label="From path" :value="$record->from_path" required placeholder="/old-address" help="The old address on this site, starting with /." />
                <x-admin.form.input name="to_path" label="To" :value="$record->to_path" required placeholder="/new-address" help="A path on this site, or a full https:// address." />
                <x-admin.form.select name="code" label="Type" :options="[301 => '301 Permanent', 302 => '302 Temporary', 307 => '307 Temporary (keeps method)', 308 => '308 Permanent (keeps method)']" :value="$record->code" required />
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create redirect' }}</button>
            </x-admin.card>
        </div>
    </div>
</form>

@if ($editing)
    <div class="mb-4"><x-admin.delete-button :action="route('admin.redirects.destroy', $record)" label="Delete redirect" confirm="Delete this redirect?" /></div>
@endif
@endsection
