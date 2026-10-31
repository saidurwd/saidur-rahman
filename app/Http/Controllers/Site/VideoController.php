<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request, SiteContent $content, SeoService $seo): View
    {
        $term = Str::of($request->string('q')->toString())->squish()->limit(100, '')->toString();
        $topics = $content->videoTopics();
        $topic = collect($topics)->first(fn (array $t) => mb_strtolower($t['name']) === mb_strtolower($request->string('topic')->toString()))['name'] ?? null;
        $page = max(1, $request->integer('page', 1));
        $searching = $term !== '';

        $videos = $searching ? $content->searchVideos($term, $page) : $content->videoPage($topic, $page);

        return view('site.videos.index', [
            'videos' => $videos,
            // Featured videos lead the unfiltered first page only.
            'featured' => ! $searching && $topic === null && $page === 1 ? $content->featuredVideos() : collect(),
            'topics' => $topics,
            'activeTopic' => $topic,
            'term' => $term,
            'searching' => $searching,
            // Page 2 and beyond are their own addresses; search results are not worth indexing.
            'canonical' => url()->current().($topic ? '?topic='.urlencode($topic).($page > 1 ? '&page='.$page : '') : ($page > 1 && ! $searching ? '?page='.$page : '')),
            'noindex' => $searching,
            'schema' => $seo->breadcrumbs([['Videos', null]]),
        ]);
    }

    public function show(string $slug, SiteContent $content, SeoService $seo): View
    {
        $video = $content->video($slug) ?? abort(404);

        return view('site.videos.show', [
            'video' => $video,
            'related' => $content->relatedVideos($video),
            'schema' => [
                $seo->video($video),
                $seo->breadcrumbs([['Videos', url('/videos')], [$video->title, null]]),
            ],
        ]);
    }
}
