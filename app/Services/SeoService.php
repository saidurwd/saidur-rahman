<?php

namespace App\Services;

use App\Models\Post;
use Carbon\CarbonInterface;

/** Builds the structured data (JSON-LD) the public pages hand to <x-site.seo>. */
class SeoService
{
    public function __construct(private SettingService $settings, private ImageStore $images) {}

    /** @return array<string, mixed> */
    public function person(): array
    {
        $sameAs = array_values(array_filter([$this->settings->get('linkedin'), $this->settings->get('github')]));
        $location = $this->settings->get('location');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $this->settings->get('site_name', config('app.name')),
            'jobTitle' => $this->settings->get('headline'),
            'description' => $this->settings->get('positioning') ?: $this->settings->get('bio'),
            'url' => url('/'),
            'image' => $this->images->url($this->settings->get('profile_photo')),
            'email' => $this->settings->get('contact_email') ? 'mailto:'.$this->settings->get('contact_email') : null,
            'address' => $location ? ['@type' => 'PostalAddress', 'addressLocality' => $location] : null,
            'sameAs' => $sameAs ?: null,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: ?string}>  $trail  name and URL pairs; the last URL may be null
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $trail): array
    {
        $items = [['Home', url('/')], ...$trail];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item[0],
                'item' => $item[1] ?? null,
            ]))->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function article(Post $post, ?string $image): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->seo_description ?: $post->excerpt,
            'image' => $image,
            'datePublished' => $this->iso($post->getAttribute('published_at')),
            'dateModified' => $this->iso($post->getAttribute('updated_at')),
            'author' => array_filter(['@type' => 'Person', 'name' => $this->settings->get('site_name', config('app.name')), 'url' => url('/about')]),
            'publisher' => ['@type' => 'Person', 'name' => $this->settings->get('site_name', config('app.name'))],
            'mainEntityOfPage' => url($post->publicPath()),
            'keywords' => $post->tags->pluck('name')->implode(', ') ?: null,
            'articleSection' => $post->category?->name,
        ]);
    }

    private function iso(mixed $date): ?string
    {
        return $date instanceof CarbonInterface ? $date->toIso8601String() : null;
    }
}
