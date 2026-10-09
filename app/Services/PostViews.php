<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Counts article views without cookies: one per visitor per article per 30 minutes, bots ignored. */
class PostViews
{
    private const BOT = '/bot|crawl|spider|slurp|preview|headless|monitor|uptime|curl|wget|python|httpclient|facebookexternalhit/i';

    public function record(Post $post, Request $request): bool
    {
        $agent = (string) $request->userAgent();

        if ($agent === '' || preg_match(self::BOT, $agent) || $request->isMethod('HEAD')) {
            return false;
        }

        $visitor = hash('sha256', $request->ip().'|'.$agent);

        if (! Cache::add("post-view.{$post->id}.{$visitor}", 1, now()->addMinutes(30))) {
            return false;
        }

        // Straight to the table: no model events, so no cache flush, activity log or updated_at change.
        DB::table('posts')->where('id', $post->id)->increment('views');

        return true;
    }
}
