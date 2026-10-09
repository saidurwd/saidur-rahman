<?php

namespace App\Providers;

use App\Models\Message;
use App\Services\NavigationService;
use App\Services\SettingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
