<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\View\View;

class ExpertiseController extends Controller
{
    public function index(SiteContent $content, SeoService $seo): View
    {
        return view('site.expertise.index', [
            'expertises' => $content->expertises(),
            'schema' => $seo->breadcrumbs([['Expertise', null]]),
        ]);
    }

    public function show(string $slug, SiteContent $content, SeoService $seo): View
    {
        $expertise = $content->expertise($slug) ?? abort(404);

        return view('site.expertise.show', [
            'expertise' => $expertise,
            'schema' => $seo->breadcrumbs([['Expertise', url('/expertise')], [$expertise->title, null]]),
        ]);
    }
}
