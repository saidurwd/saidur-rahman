<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\CompleteAdminLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\TrustedDevices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request, TrustedDevices $trusted, CompleteAdminLogin $complete): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $request->session()->forget(CompleteAdminLogin::SESSION_KEY);

        // A browser the admin chose to trust after a correct code goes straight in, for as long as the trust lasts.
        $user = $request->user();
        if ($user->hasTwoFactorEnabled() && $trusted->isTrusted($request, $user)) {
            $complete($request, $user);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
