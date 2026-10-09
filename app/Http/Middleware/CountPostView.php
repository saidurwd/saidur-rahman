<?php

namespace App\Http\Middleware;

use App\Services\PostViews;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts an article view. It sits outside the full-page cache so views are still counted when the
 * page itself comes from the cache. Only successful loads count, so drafts and 404s never do.
 */
class CountPostView
{
    public function __construct(private PostViews $views) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $slug = $request->route('slug');

        if ($response->getStatusCode() === 200 && is_string($slug)) {
            defer(fn () => $this->views->record($slug, $request));
        }

        return $response;
    }
}
