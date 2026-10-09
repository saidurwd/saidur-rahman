<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ImageStore;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Services\SiteContent;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SiteContent $content, SettingService $settings, SeoService $seo, ImageStore $images): View
    {
        return view('site.pages.home', [
            'stats' => $settings->get('stats', []),
            'projects' => $content->featuredProjects(3),
            'experiences' => $content->experiences()->take(3),
            'pillars' => $content->skillGroups()->take(3),
            'posts' => Route::has('blog.index') ? $content->latestPosts(3) : collect(),
            'hasCv' => $content->hasActiveCv(),
            'portrait' => $images->url($settings->get('profile_photo')),
            'schema' => $seo->person(),
        ]);
    }
}
