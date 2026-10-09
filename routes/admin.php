<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\TwoFactorSetupController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\ComponentDemoController;
use App\Http\Controllers\Admin\CvFileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SkillGroupController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TestimonialController;
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

    // Content modules. Projects is the reference; see docs/ADMIN_MODULE_PATTERN.md.
    Route::adminModule('projects', ProjectController::class);
    Route::adminModule('experiences', ExperienceController::class);
    Route::adminModule('educations', EducationController::class);
    Route::adminModule('certifications', CertificationController::class);
    Route::adminModule('skill-groups', SkillGroupController::class);
    Route::adminModule('skills', SkillController::class);
    Route::adminModule('services', ServiceController::class);
    Route::adminModule('testimonials', TestimonialController::class);
    Route::adminModule('pages', PageController::class);
    Route::adminModule('menus', MenuController::class);
    Route::adminModule('posts', PostController::class, ['reorder' => false]);
    Route::adminModule('categories', CategoryController::class);
    Route::post('tags/bulk', [TagController::class, 'bulk'])->name('tags.bulk');
    Route::resource('tags', TagController::class)->except('show')->whereNumber('tag');
    Route::adminModule('cv-files', CvFileController::class, ['reorder' => false]);
    Route::post('cv-files/{cv_file}/activate', [CvFileController::class, 'activate'])->whereNumber('cv_file')->name('cv-files.activate');
    Route::get('cv-files/{cv_file}/download', [CvFileController::class, 'download'])->whereNumber('cv_file')->name('cv-files.download');
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('redirects/bulk', [RedirectController::class, 'bulk'])->name('redirects.bulk');
    Route::resource('redirects', RedirectController::class)->except('show')->whereNumber('redirect');

    Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

    if (app()->environment(['local', 'testing'])) {
        Route::get('components', ComponentDemoController::class)->name('components');
    }
});
