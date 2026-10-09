<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\TwoFactorSetupController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\ComponentDemoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PasswordController;
use Illuminate\Support\Facades\Route;

// Prefix `admin`, name `admin.` and the web middleware are applied in bootstrap/app.php.

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// Signed in, 2FA not necessarily complete yet.
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('two-factor/challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('two-factor/challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.challenge.store');
});

// Setup is reachable before 2FA is enabled; once enabled it needs a passed challenge.
Route::middleware(['auth', 'admin.2fa:allow-setup'])->group(function () {
    Route::get('two-factor', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
    Route::post('two-factor', [TwoFactorSetupController::class, 'store'])->name('two-factor.confirm');
    Route::post('two-factor/recovery-codes', [TwoFactorSetupController::class, 'regenerate'])->name('two-factor.regenerate');
});

// The admin proper: signed in with 2FA passed.
Route::middleware(['auth', 'admin.2fa'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

    if (app()->environment(['local', 'testing'])) {
        Route::get('components', ComponentDemoController::class)->name('components');
    }
});
