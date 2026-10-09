<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Services\SiteContent;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(SiteContent $content, SettingService $settings): Response
    {
        $posts = $content->feedPosts();

        $xml = view('site.blog.feed', [
            'title' => $settings->get('site_name', config('app.name')).' — Articles',
            'description' => $settings->get('positioning') ?: (string) $settings->get('bio'),
            'posts' => $posts,
            'updated' => $posts->max('published_at') ?? now(),
        ])->render();

        // The XML declaration is added here because Blade would read "<?xml" as the start of PHP code.
        return response('<?xml version="1.0" encoding="UTF-8"?>'."\n".$xml)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
