<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    @include('admin.partials.head')
</head>
<body class="bg-body-tertiary">
<div class="login-page">
    <div class="login-box" style="width: min(26rem, 92vw)">
        <div class="text-center mb-4">
            <x-admin.monogram style="width:3.5rem;height:3.5rem" />
            <h1 class="h4 mt-3 mb-0">{{ config('app.name') }}</h1>
            <p class="text-body-secondary small mb-0">Admin</p>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-body login-card-body p-4">
                @yield('content')
            </div>
        </div>
    </div>
</div>
@include('admin.partials.toasts')
</body>
</html>
