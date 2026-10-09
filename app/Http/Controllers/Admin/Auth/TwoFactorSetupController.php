<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\CompleteAdminLogin;
use App\Actions\Auth\EnableTwoFactor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Admin\Auth\RegenerateRecoveryCodesRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    private const PENDING_SECRET = 'two_factor_pending_secret';

    public function show(Request $request, TwoFactorService $twoFactor): View
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('admin.auth.two-factor-setup', [
                'enabled' => true,
                'recoveryCodes' => $request->session()->get('recovery_codes', []),
                'remaining' => count($user->recoveryCodes()),
            ]);
        }

        $secret = $request->session()->get(self::PENDING_SECRET) ?? $twoFactor->generateSecret();
        $request->session()->put(self::PENDING_SECRET, $secret);

        return view('admin.auth.two-factor-setup', [
            'enabled' => false,
            'secret' => $secret,
            'qrSvg' => $twoFactor->qrSvg($user, $secret),
            'recoveryCodes' => [],
        ]);
    }

    public function store(
        ConfirmTwoFactorRequest $request,
        TwoFactorService $twoFactor,
        EnableTwoFactor $enable,
        CompleteAdminLogin $complete,
    ): RedirectResponse {
        $user = $request->user();
        $secret = $request->session()->get(self::PENDING_SECRET);

        if ($user->hasTwoFactorEnabled() || ! $secret) {
            return redirect()->route('admin.two-factor.setup');
        }

        if (! $twoFactor->verify($secret, (string) $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'The code is not valid. Check your authenticator app and try again.']);
        }

        $codes = $enable($user, $secret);
        $request->session()->forget(self::PENDING_SECRET);
        $complete($request, $user);

        return redirect()->route('admin.two-factor.setup')->with('recovery_codes', $codes);
    }

    public function regenerate(RegenerateRecoveryCodesRequest $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $codes = $twoFactor->generateRecoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => json_encode($codes)])->save();

        return redirect()->route('admin.two-factor.setup')->with('recovery_codes', $codes);
    }
}
