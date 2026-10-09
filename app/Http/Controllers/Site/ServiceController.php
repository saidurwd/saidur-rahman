<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(SiteContent $content, SeoService $seo): View
    {
        return view('site.services.index', [
            'services' => $content->services(),
            'schema' => $seo->breadcrumbs([['Services', null]]),
        ]);
    }

    public function show(string $slug, SiteContent $content, SeoService $seo): View
    {
        $service = $content->service($slug) ?? abort(404);

        return view('site.services.show', [
            'service' => $service,
            'schema' => $seo->breadcrumbs([['Services', url('/services')], [$service->title, null]]),
        ]);
    }
}
