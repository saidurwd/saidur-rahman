<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        // Anything that is not the live site (staging, a preview) asks crawlers to stay away entirely.
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /cv/download/', 'Disallow: /*?q=', 'Allow: /', '', 'Sitemap: '.url('/sitemap.xml')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
