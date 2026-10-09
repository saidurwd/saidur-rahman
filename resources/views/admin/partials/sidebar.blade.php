<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex align-items-center gap-2">
            <x-admin.monogram />
            <span class="brand-text">{{ config('app.name') }}</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Admin">
            <ul class="nav sidebar-menu flex-column" role="menu">
                @foreach (config('admin.menu') as $item)
                    @if (isset($item['header']))
                        <li class="nav-header">{{ strtoupper($item['header']) }}</li>
                        @continue
                    @endif

                    @php
                        $exists = Route::has($item['route']);
                        $active = $exists && request()->routeIs($item['route']);
                        $badge = isset($item['badge']) ? ($sidebarBadges[$item['badge']] ?? 0) : 0;
                    @endphp
                    <li class="nav-item">
                        @if ($exists && ($item['method'] ?? 'get') === 'post')
                            <form method="POST" action="{{ route($item['route']) }}">
                                @csrf
                                <button type="submit" class="nav-link w-100 text-start bg-transparent border-0">
                                    <i class="nav-icon bi bi-{{ $item['icon'] }}"></i><p>{{ $item['label'] }}</p>
                                </button>
                            </form>
                        @else
                            <a href="{{ $exists ? route($item['route']) : '#' }}"
                               @class(['nav-link', 'active' => $active, 'disabled' => ! $exists])
                               @if (! $exists) aria-disabled="true" tabindex="-1" title="Coming in a later phase" @endif
                               @if ($active) aria-current="page" @endif>
                                <i class="nav-icon bi bi-{{ $item['icon'] }}"></i>
                                <p>
                                    {{ $item['label'] }}
                                    @if ($badge > 0)<span class="nav-badge badge text-bg-primary me-3">{{ $badge }}</span>@endif
                                </p>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
