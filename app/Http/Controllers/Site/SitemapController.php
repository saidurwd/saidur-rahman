<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SitemapGenerator;
use Illuminate\Http\Response;

/** Fallback for when the nightly file is missing; normally the web server serves public/sitemap.xml itself. */
class SitemapController extends Controller
{
    public function __invoke(SitemapGenerator $sitemap): Response
    {
        return response($sitemap->xml())->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
