<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expertise;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Support\Carbon;

/** Builds sitemap.xml from published content. Only pages that a visitor can actually open are listed. */
class SitemapGenerator
{
    public const FILE = 'sitemap.xml';

    /** @return list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}> */
    public function entries(): array
    {
        $entries = [
            $this->entry('/', null, 'weekly', '1.0'),
            $this->entry('/about', null, 'monthly', '0.8'),
            $this->entry('/experience', null, 'monthly', '0.7'),
            $this->entry('/projects', Project::published()->max('updated_at'), 'monthly', '0.9'),
            $this->entry('/blog', Post::published()->max('updated_at'), 'weekly', '0.8'),
            $this->entry('/contact', null, 'yearly', '0.5'),
            $this->entry('/cv', null, 'monthly', '0.4'),
        ];

        foreach (Project::published()->ordered()->get() as $project) {
            $entries[] = $this->entry($project->publicPath(), $project->updated_at, 'monthly', '0.8');
        }

        foreach (Post::published()->orderByDesc('published_at')->get() as $post) {
            $entries[] = $this->entry($post->publicPath(), $post->updated_at, 'monthly', '0.7');
        }

        $categories = Category::query()->whereHas('posts', fn ($q) => $q->published())->ordered()->get();
        foreach ($categories as $category) {
            $entries[] = $this->entry($category->publicPath(), null, 'weekly', '0.4');
        }

        $expertises = Expertise::published()->ordered()->get();
        if ($expertises->isNotEmpty()) {
            $entries[] = $this->entry('/expertise', $expertises->max('updated_at'), 'monthly', '0.6');
        }
        foreach ($expertises as $expertise) {
            $entries[] = $this->entry($expertise->publicPath(), $expertise->updated_at, 'monthly', '0.5');
        }

        foreach (Page::published()->ordered()->get() as $page) {
            $entries[] = $this->entry($page->publicPath(), $page->updated_at, 'yearly', '0.3');
        }

        return $entries;
    }

    public function xml(): string
    {
        $rows = collect($this->entries())->map(fn (array $e) => '  <url>'
            .'<loc>'.htmlspecialchars($e['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'
            .($e['lastmod'] ? '<lastmod>'.$e['lastmod'].'</lastmod>' : '')
            .'<changefreq>'.$e['changefreq'].'</changefreq><priority>'.$e['priority'].'</priority>'
            .'</url>');

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$rows->implode("\n")."\n</urlset>\n";
    }

    /** Writes the file into public/ so the web server can serve it without starting PHP. */
    public function write(): string
    {
        $path = public_path(self::FILE);
        file_put_contents($path, $this->xml());

        return $path;
    }

    /** @return array{loc: string, lastmod: ?string, changefreq: string, priority: string} */
    private function entry(string $path, mixed $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => url($path),
            'lastmod' => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
