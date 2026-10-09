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
use App\Http\Controllers\Site\RobotsController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\StyleguideController;
use App\Http\Middleware\CountPostView;
use App\Http\Middleware\TrackPageView;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\ResponseCache\Middlewares\CacheResponse;

/*
 * Public pages carry no cookies at all: the session, cookie and CSRF middleware are removed, so every visitor
 * gets the same response and it can be served from the full-page cache (Spatie ResponseCache, cleared whenever
 * the admin saves content). TrackPageView counts the visit even when the page comes from the cache.
 */
$cookieless = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
];

Route::middleware(TrackPageView::class)->withoutMiddleware($cookieless)->group(function () {
    Route::middleware(CacheResponse::class)->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/about', AboutController::class)->name('about');
        Route::get('/experience', ExperienceController::class)->name('experience');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');

        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
        Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
        Route::get('/feed', FeedController::class)->name('feed');

        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

        Route::get('/cv', [CvController::class, 'show'])->name('cv');
        Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
        Route::get('/robots.txt', RobotsController::class)->name('robots');
    });

    // The view counter has to run even when the article comes from the cache, so it sits outside it.
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->middleware([CountPostView::class, CacheResponse::class])->name('blog.show');
});

// The contact form needs a session (flash messages, CSRF), so it is the one public page that keeps cookies and is never cached.
Route::middleware(TrackPageView::class)->group(function () {
    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
});

Route::get('/cv/download/{cvFile}', [CvController::class, 'download'])
    ->withoutMiddleware($cookieless)->whereNumber('cvFile')->middleware('throttle:60,1')->name('cv.download');

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Editable pages (privacy and the like) answer any path nothing else claimed. A fallback route
// is used so the admin and every later route keep priority; unknown paths still end in a 404.
Route::fallback(PageController::class)->withoutMiddleware($cookieless)->middleware([TrackPageView::class, CacheResponse::class]);
