@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Security' => null]])

@section('title', 'Two-factor authentication')

@section('content')
    <div class="row">
        <div class="col-lg-7">
            @if (! $enabled)
                <x-admin.card title="Set up an authenticator app">
                    <p>Two-factor authentication keeps the admin safe even if your password leaks. Scan the QR code with an authenticator app (1Password, Authy, Google Authenticator), then enter the 6-digit code to finish.</p>
                    <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
                        <div class="bg-white p-2 rounded" style="width: 12rem" aria-label="QR code">{!! $qrSvg !!}</div>
                        <div>
                            <div class="small text-body-secondary">Can't scan? Enter this key manually:</div>
                            <code class="user-select-all">{{ $secret }}</code>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.two-factor.confirm') }}">
                        @csrf
                        <x-admin.form.input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="otp-input" style="max-width: 14rem" required />
                        <button type="submit" class="btn btn-primary">Confirm and enable</button>
                    </form>
                </x-admin.card>
            @else
                <x-admin.card title="Authenticator app">
                    <p class="mb-0"><i class="bi bi-shield-check text-success me-1"></i>Two-factor authentication is enabled. {{ $remaining }} recovery {{ Str::plural('code', $remaining) }} remaining.</p>
                </x-admin.card>

                <x-admin.card title="Recovery codes">
                    @if ($recoveryCodes)
                        <div class="alert alert-warning">Save these codes somewhere safe. Each works once, and they will not be shown again.</div>
                        <ul class="list-unstyled font-monospace fs-5">
                            @foreach ($recoveryCodes as $code)<li>{{ $code }}</li>@endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('admin.two-factor.regenerate') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-sm-7">
                            <x-admin.form.input name="current_password" type="password" label="Current password" autocomplete="current-password" />
                        </div>
                        <div class="col-sm-5 mb-3">
                            <button type="submit" class="btn btn-outline-primary">Generate new codes</button>
                        </div>
                    </form>
                </x-admin.card>

                <x-admin.card title="Trusted browsers">
                    <p class="text-body-secondary">After a correct code you can choose “Trust this browser for {{ config('admin.trust_days') }} days” on the sign-in screen, and that browser skips the code until the trust runs out. Changing your password, turning two-factor off and on, or the button below ends the trust on every browser.</p>
                    <form method="POST" action="{{ route('admin.two-factor.forget-devices') }}" data-confirm="Forget every trusted browser? You will be asked for a code on your next sign-in.">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Forget trusted browsers</button>
                    </form>
                </x-admin.card>

                <x-admin.card title="Turn off two-factor authentication">
                    <p class="text-body-secondary">Not recommended: the admin would be protected by your password alone. To confirm it is you, enter your password and a code from the app (or a recovery code).</p>
                    <form method="POST" action="{{ route('admin.two-factor.disable') }}" data-confirm="Turn off two-factor authentication?">
                        @csrf
                        <x-admin.form.input name="current_password" type="password" label="Current password" autocomplete="current-password" required />
                        <div class="row">
                            <div class="col-sm-6"><x-admin.form.input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" /></div>
                            <div class="col-sm-6"><x-admin.form.input name="recovery_code" label="…or a recovery code" autocomplete="off" /></div>
                        </div>
                        <button type="submit" class="btn btn-outline-danger">Turn off</button>
                    </form>
                </x-admin.card>
            @endif
        </div>
    </div>
@endsection
