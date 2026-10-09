<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('home') }}" class="nav-link" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View site</a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                @php $messagesUrl = Route::has('admin.messages.index') ? route('admin.messages.index') : '#'; @endphp
                <a class="nav-link position-relative" href="{{ $messagesUrl }}" aria-label="Unread messages">
                    <i class="bi bi-bell"></i>
                    @if (($sidebarBadges['unread_messages'] ?? 0) > 0)
                        <span class="navbar-badge badge text-bg-danger">{{ $sidebarBadges['unread_messages'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item dropdown">
                <button class="btn btn-link nav-link dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-label="Theme">
                    <i class="bi bi-circle-half" data-lte-theme-icon="auto"></i>
                    <i class="bi bi-sun-fill d-none" data-lte-theme-icon="light"></i>
                    <i class="bi bi-moon-stars-fill d-none" data-lte-theme-icon="dark"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" type="button" data-bs-theme-value="light">Light</button></li>
                    <li><button class="dropdown-item" type="button" data-bs-theme-value="dark">Dark</button></li>
                    <li><button class="dropdown-item" type="button" data-bs-theme-value="auto">System</button></li>
                </ul>
            </li>
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <x-admin.monogram class="me-1" style="width:1.6rem;height:1.6rem" />
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        <div class="small text-body-secondary">{{ auth()->user()->email }}</div>
                        @if (auth()->user()->last_login_at)
                            <div class="small text-body-secondary">Last login {{ auth()->user()->last_login_at->diffForHumans() }}</div>
                        @endif
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('admin.two-factor.setup') }}"><i class="bi bi-shield-lock me-2"></i>Security</a></li>
                    @if (Route::has('admin.password.edit'))
                        <li><a class="dropdown-item" href="{{ route('admin.password.edit') }}"><i class="bi bi-key me-2"></i>Password</a></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
