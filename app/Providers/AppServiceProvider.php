<?php

namespace App\Providers;

use App\Models\Message;
use App\Services\NavigationService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registers the standard admin module routes: resource (no show) plus trash, restore,
        // force delete, and optionally reorder and bulk. Must be called inside the admin group.
        Route::macro('adminModule', function (string $name, string $controller, array $options = []): void {
            $reorder = $options['reorder'] ?? true;
            $bulk = $options['bulk'] ?? true;

            Route::get("{$name}/trash", [$controller, 'trash'])->name("{$name}.trash");
            if ($reorder) {
                Route::post("{$name}/reorder", [$controller, 'reorder'])->name("{$name}.reorder");
            }
            if ($bulk) {
                Route::post("{$name}/bulk", [$controller, 'bulk'])->name("{$name}.bulk");
            }
            Route::post("{$name}/{id}/restore", [$controller, 'restore'])->whereNumber('id')->name("{$name}.restore");
            Route::delete("{$name}/{id}/force", [$controller, 'forceDelete'])->whereNumber('id')->name("{$name}.force-delete");
            $param = Str::singular(str_replace('-', '_', $name));
            Route::resource($name, $controller)->except('show')->parameters([$name => $param])->whereNumber($param);
        });

        View::composer('site.layouts.app', function ($view): void {
            $settings = app(SettingService::class);
            $navigation = app(NavigationService::class);

            $view->with([
                'siteName' => $settings->get('site_name', config('app.name')),
                'headerMenu' => $navigation->items('header'),
                'footerMenu' => $navigation->items('footer'),
                'socialLinks' => array_filter([
                    'LinkedIn' => $settings->get('linkedin'),
                    'GitHub' => $settings->get('github'),
                ]),
                'contactEmail' => $settings->get('contact_email'),
                'availability' => $settings->get('availability_open') ? $settings->get('availability_text') : null,
            ]);
        });

        View::composer(['admin.partials.sidebar', 'admin.partials.navbar'], function ($view): void {
            $view->with('sidebarBadges', ['unread_messages' => Message::unread()->count()]);
        });
    }
}
