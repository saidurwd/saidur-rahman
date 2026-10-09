<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function __invoke(SiteContent $content, SeoService $seo): View
    {
        return view('site.pages.experience', [
            'experiences' => $content->experiences(),
            'schema' => $seo->breadcrumbs([['Experience', null]]),
        ]);
    }
}
