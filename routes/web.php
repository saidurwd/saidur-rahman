<?php

use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\CvController;
use App\Http\Controllers\Site\ExperienceController;
use App\Http\Controllers\Site\FeedController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\StyleguideController;
use App\Http\Middleware\TrackPageView;
use Illuminate\Support\Facades\Route;

// Public pages record a page view (no cookies, bots and Do Not Track skipped).
Route::middleware(TrackPageView::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/experience', ExperienceController::class)->name('experience');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/feed', FeedController::class)->name('feed');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

    Route::get('/cv', [CvController::class, 'show'])->name('cv');
    Route::get('/cv/download/{cvFile}', [CvController::class, 'download'])->whereNumber('cvFile')->middleware('throttle:60,1')->name('cv.download');
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Editable pages (privacy and the like) answer any path nothing else claimed. A fallback route
// is used so the admin and every later route keep priority; unknown paths still end in a 404.
Route::fallback(PageController::class)->middleware(TrackPageView::class);
