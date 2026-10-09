<?php

namespace App\Services;

use App\Models\Menu;

class NavigationService
{
    public function __construct(private CacheService $cache) {}

    /** @return list<array{label: string, href: string}> */
    public function items(string $location): array
    {
        /** @var list<array{label: string, href: string}> */
        return $this->cache->remember(CacheService::CONTENT, "menu.{$location}", null, fn () => Menu::query()
            ->published()
            ->location($location)
            ->ordered()
            ->get()
            ->map(fn (Menu $menu) => ['label' => $menu->label, 'href' => $menu->href()])
            ->filter(fn (array $item) => $item['href'] !== '')
            ->values()
            ->all());
    }
}
