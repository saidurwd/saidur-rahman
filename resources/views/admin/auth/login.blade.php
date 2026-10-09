@extends('admin.layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h2 class="h5 mb-3">Sign in</h2>
    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <x-admin.form.input name="email" type="email" label="Email" required autofocus autocomplete="username" />
        <x-admin.form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <x-admin.form.toggle name="remember" label="Remember me" />
        <button type="submit" class="btn btn-primary w-100">Sign in</button>
    </form>
@endsection
