<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Which public responses may be served from the full-page cache. Only plain GET requests for pages
 * that look the same for everyone: no search results, no forms, nothing that sets a cookie.
 */
class PublicPageCache extends CacheAllSuccessfulGetRequests
{
    /** Query parameters that change what a page shows. Anything else (search terms and so on) is not cached. */
    private const ALLOWED_QUERY = ['page', 'tech'];

    public function shouldCacheRequest(Request $request): bool
    {
        if (! parent::shouldCacheRequest($request)) {
            return false;
        }

        return collect($request->query())->keys()->diff(self::ALLOWED_QUERY)->isEmpty()
            && ! $request->is('admin', 'admin/*', 'contact', 'cv/download/*');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        // Only real pages: not redirects (recorded redirects can change without a cache flush), and never a response that sets a cookie.
        return $response->getStatusCode() === 200
            && ! $response->headers->has('Set-Cookie')
            && parent::shouldCacheResponse($response);
    }

    /** Everyone shares one copy, signed in or not: these pages do not change for the admin. */
    public function useCacheNameSuffix(Request $request): string
    {
        return '';
    }
}
