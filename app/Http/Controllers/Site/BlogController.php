<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ArticleHtml;
use App\Services\SeoService;
use App\Services\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request, SiteContent $content, SeoService $seo): View
    {
        $term = Str::of($request->string('q')->toString())->squish()->limit(100, '')->toString();
        $page = max(1, $request->integer('page', 1));

        return $this->listing($request, $content, $seo, [
            'posts' => $term !== '' ? $content->searchPosts($term, $page) : $content->blogPage(null, null, $page),
            'term' => $term,
            'heading' => 'Articles',
            'breadcrumbs' => ['Articles' => null],
        ]);
    }

    public function category(string $slug, Request $request, SiteContent $content, SeoService $seo): View
    {
        $category = $content->category($slug) ?? abort(404);

        return $this->listing($request, $content, $seo, [
            'posts' => $content->blogPage($slug, null, max(1, $request->integer('page', 1))),
            'term' => '',
            'heading' => $category['name'],
            'activeCategory' => $slug,
            'breadcrumbs' => ['Articles' => route('blog.index'), $category['name'] => null],
        ]);
    }

    public function tag(string $slug, Request $request, SiteContent $content, SeoService $seo): View
    {
        $tag = $content->tag($slug) ?? abort(404);

        return $this->listing($request, $content, $seo, [
            'posts' => $content->blogPage(null, $slug, max(1, $request->integer('page', 1))),
            'term' => '',
            'heading' => "Tagged: {$tag->name}",
            'breadcrumbs' => ['Articles' => route('blog.index'), $tag->name => null],
        ]);
    }

    public function show(string $slug, Request $request, SiteContent $content, SeoService $seo, ArticleHtml $html): View
    {
        $post = $content->post($slug) ?? abort(404);

        $cover = $post->getFirstMedia('cover');
        $image = $cover ? ($cover->hasGeneratedConversion('large') ? $cover->getUrl('large') : $cover->getUrl()) : null;

        return view('site.blog.show', [
            'post' => $post,
            'article' => $html->process($post->body),
            'related' => $content->related($post),
            'cover' => $cover,
            'image' => $image,
            'schema' => [
                $seo->article($post, $image),
                $seo->breadcrumbs([['Articles', url('/blog')], [$post->title, null]]),
            ],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function listing(Request $request, SiteContent $content, SeoService $seo, array $data): View
    {
        $page = $data['posts']->currentPage();
        $searching = $data['term'] !== '';

        return view('site.blog.index', $data + [
            'categories' => $content->categories(),
            'activeCategory' => null,
            'searching' => $searching,
            // Page 2 and beyond are their own addresses; search results are not worth indexing.
            'canonical' => url()->current().($page > 1 && ! $searching ? '?page='.$page : ''),
            'noindex' => $searching,
            'schema' => $seo->breadcrumbs($this->trail($data['breadcrumbs'])),
        ]);
    }

    /**
     * @param  array<string, ?string>  $breadcrumbs  label => url
     * @return list<array{0: string, 1: ?string}>
     */
    private function trail(array $breadcrumbs): array
    {
        $trail = [];

        foreach ($breadcrumbs as $label => $url) {
            $trail[] = [$label, $url];
        }

        return $trail;
    }
}
