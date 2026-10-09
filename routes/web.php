<?php

use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\ExperienceController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/about', AboutController::class)->name('about');
Route::get('/experience', ExperienceController::class)->name('experience');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Editable pages (privacy and the like) answer any path nothing else claimed. A fallback route
// is used so the admin and every later route keep priority; unknown paths still end in a 404.
Route::fallback(PageController::class);
