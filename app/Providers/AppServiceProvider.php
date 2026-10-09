<?php

namespace App\Providers;

use App\Listeners\StoreMediaDimensions;
use App\Models\Message;
use App\Policies\ActivityPolicy;
use App\Policies\MediaPolicy;
use App\Services\SettingService;
use App\Services\SiteShell;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SettingService::class);
        $this->app->scoped(SiteShell::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vendor models are not auto-discovered, so their admin policies are registered by hand.
        Event::listen(MediaHasBeenAddedEvent::class, StoreMediaDimensions::class);

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);

        // Registers the standard admin module routes: resource (no show) plus trash, restore,
        // force delete, and optionally reorder and bulk. Must be called inside the admin group.
        Route::macro('adminModule', function (string $name, string $controller, array $options = []): void {
            $reorder = $options['reorder'] ?? true;
            $bulk = $options['bulk'] ?? true;
            $trash = $options['trash'] ?? true;

            if ($trash) {
                Route::get("{$name}/trash", [$controller, 'trash'])->name("{$name}.trash");
                Route::post("{$name}/{id}/restore", [$controller, 'restore'])->whereNumber('id')->name("{$name}.restore");
                Route::delete("{$name}/{id}/force", [$controller, 'forceDelete'])->whereNumber('id')->name("{$name}.force-delete");
            }
            if ($reorder) {
                Route::post("{$name}/reorder", [$controller, 'reorder'])->name("{$name}.reorder");
            }
            if ($bulk) {
                Route::post("{$name}/bulk", [$controller, 'bulk'])->name("{$name}.bulk");
            }
            $param = Str::singular(str_replace('-', '_', $name));
            Route::resource($name, $controller)->except('show')->parameters([$name => $param])->whereNumber($param);
        });

        View::composer('site.*', fn ($view) => $view->with(app(SiteShell::class)->data()));

        View::composer(['admin.partials.sidebar', 'admin.partials.navbar'], function ($view): void {
            $view->with('sidebarBadges', ['unread_messages' => Message::unread()->count()]);
        });
    }
}
