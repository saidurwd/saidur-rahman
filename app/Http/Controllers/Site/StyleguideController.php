<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Local-only reference page for the design system. */
class StyleguideController extends Controller
{
    public function __invoke(): View
    {
        return view('site.styleguide.index');
    }
}
