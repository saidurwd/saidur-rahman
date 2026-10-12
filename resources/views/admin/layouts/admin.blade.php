<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    @include('admin.partials.head')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    @include('admin.partials.navbar')
    @include('admin.partials.sidebar')

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="mb-0 fs-3">@yield('title', 'Admin')</h1>
                    </div>
                    <div class="col-sm-6">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb float-sm-end mb-0">
                                @foreach ($breadcrumbs ?? [] as $label => $url)
                                    @if ($url && ! $loop->last)
                                        <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                                    @else
                                        <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                @unless (auth()->user()->hasTwoFactorEnabled())
                    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-shield-exclamation"></i>
                        <div>Two-factor authentication is off, so only your password protects this admin. <a href="{{ route('admin.two-factor.setup') }}" class="alert-link">Turn it on</a>.</div>
                    </div>
                @endunless
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Laravel {{ app()->version() }}</div>
        <strong>{{ config('app.name') }}</strong> admin
    </footer>
</div>
@include('admin.partials.toasts')
</body>
</html>
