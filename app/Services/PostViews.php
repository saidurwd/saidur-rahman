<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Counts article views without cookies: one per visitor per article per 30 minutes, bots ignored. */
class PostViews
{
    public function record(string $slug, Request $request): bool
    {
        if (Bots::isBot($request->userAgent()) || $request->isMethod('HEAD')) {
            return false;
        }

        $visitor = hash('sha256', $request->ip().'|'.$request->userAgent());

        if (! Cache::add("post-view.{$slug}.{$visitor}", 1, now()->addMinutes(30))) {
            return false;
        }

        // Straight to the table: no model events, so no cache flush, activity log or updated_at change.
        return DB::table('posts')->where('slug', $slug)->increment('views') > 0;
    }
}
