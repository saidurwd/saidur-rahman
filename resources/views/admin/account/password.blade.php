@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Password' => null]])

@section('title', 'Change password')

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <x-admin.card>
                <form method="POST" action="{{ route('admin.password.update') }}">
                    @csrf
                    @method('PUT')
                    <x-admin.form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                    <x-admin.form.input name="password" type="password" label="New password" required autocomplete="new-password" help="At least 12 characters, with upper and lower case letters and a number." />
                    <x-admin.form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                    <button type="submit" class="btn btn-primary">Update password</button>
                </form>
            </x-admin.card>
        </div>
    </div>
@endsection
