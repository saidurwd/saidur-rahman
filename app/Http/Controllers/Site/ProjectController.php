<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, SiteContent $content, SeoService $seo): View
    {
        $tags = $content->techTags();
        $tech = collect($tags)->first(fn (string $tag) => mb_strtolower($tag) === mb_strtolower($request->string('tech')->toString()));

        return view('site.projects.index', [
            'projects' => $tech
                ? $content->projects()->filter(fn ($p) => $p->usesTech($tech))->values()
                : $content->projects(),
            'tags' => $tags,
            'activeTech' => $tech,
            'schema' => $seo->breadcrumbs([['Projects', null]]),
        ]);
    }

    public function show(string $slug, SiteContent $content, SeoService $seo): View
    {
        $project = $content->project($slug) ?? abort(404);

        return view('site.projects.show', [
            'project' => $project,
            'neighbours' => $content->neighbours($project),
            'schema' => $seo->breadcrumbs([['Projects', url('/projects')], [$project->title, null]]),
        ]);
    }
}
