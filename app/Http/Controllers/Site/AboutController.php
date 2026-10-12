<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ImageStore;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Services\SiteContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(SiteContent $content, SeoService $seo, SettingService $settings, ImageStore $images): View
    {
        return view('site.pages.about', [
            'portrait' => $images->url($settings->get('profile_photo')),
            'testimonials' => $content->testimonials(),
            'skillGroups' => $content->skillGroups(),
            'certifications' => $content->certifications(),
            'education' => $content->education(),
            'schema' => [$seo->person(), $seo->breadcrumbs([['About', null]])],
        ]);
    }
}
