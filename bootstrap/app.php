<?php

use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use App\Services\RedirectService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Nginx and Cloudflare the real client address and the https scheme arrive in forwarded headers.
        // Set TRUSTED_PROXIES to the proxy addresses (comma separated), or * only if the server accepts traffic from nothing but them.
        $proxies = (string) env('TRUSTED_PROXIES', '');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))));

        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias(['admin.2fa' => EnsureTwoFactor::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('home'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Old URLs whose slug changed are served as redirects instead of 404s.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->isMethod('GET') && ! $request->is('admin', 'admin/*')) {
                $redirect = app(RedirectService::class)->resolve($request->getPathInfo());

                if ($redirect) {
                    return redirect($redirect->to_path, $redirect->code);
                }
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
