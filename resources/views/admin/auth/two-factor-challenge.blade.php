@extends('admin.layouts.auth')

@section('title', 'Two-factor check')

@section('content')
    <h2 class="h5 mb-1">Two-factor check</h2>
    <p class="text-body-secondary small">Enter the 6-digit code from your authenticator app.</p>

    <form method="POST" action="{{ route('admin.two-factor.challenge.store') }}">
        @csrf
        <x-admin.form.input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" class="otp-input" autofocus />
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember_device" value="1" id="remember_device">
            <label class="form-check-label" for="remember_device">Trust this browser for {{ config('admin.trust_days') }} days</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verify</button>
    </form>

    <details class="mt-4" @if ($errors->has('recovery_code')) open @endif>
        <summary class="small text-body-secondary">Use a recovery code instead</summary>
        <form method="POST" action="{{ route('admin.two-factor.challenge.store') }}" class="mt-3">
            @csrf
            <x-admin.form.input name="recovery_code" label="Recovery code" autocomplete="off" placeholder="xxxxx-xxxxx" />
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember_device" value="1" id="remember_device_rc">
                <label class="form-check-label" for="remember_device_rc">Trust this browser for {{ config('admin.trust_days') }} days</label>
            </div>
            <button type="submit" class="btn btn-outline-primary w-100">Use recovery code</button>
        </form>
    </details>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-body-secondary">Cancel and log out</button>
    </form>
@endsection
