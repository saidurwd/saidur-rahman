<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Services\Analytics;
use App\Services\Bots;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight first-party analytics for public pages. Records successful HTML page loads from
 * people (not bots, not visitors who opted out) after the response is sent, so it never slows a page down.
 */
class TrackPageView
{
    public function __construct(private Analytics $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $record = [
                'path' => mb_substr('/'.trim($request->path(), '/'), 0, 255),
                'ip_hash' => $this->analytics->visitorHash($request),
                'country' => $this->analytics->country($request),
                'referrer' => $this->analytics->referrerHost($request),
                'device' => $this->analytics->device($request->userAgent()),
            ];

            defer(fn () => PageView::query()->create($record));
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->is('admin', 'admin/*', 'styleguide')
            && ! Bots::isBot($request->userAgent())
            && ! $this->analytics->optedOut($request);
    }
}
