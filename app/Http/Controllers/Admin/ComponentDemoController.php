<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Local-only page that renders every admin component. */
class ComponentDemoController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.demo');
    }
}
