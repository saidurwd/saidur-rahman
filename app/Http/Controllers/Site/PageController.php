<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(Request $request, SiteContent $content, SeoService $seo): View
    {
        $slug = trim($request->path(), '/');

        abort_unless($request->isMethod('GET') && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug), 404);

        $page = $content->page($slug) ?? abort(404);

        return view('site.pages.page', [
            'page' => $page,
            'schema' => $seo->breadcrumbs([[$page->title, null]]),
        ]);
    }
}
