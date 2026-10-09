<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $header = [
            ['About', '/about'], ['Experience', '/experience'], ['Projects', '/projects'],
            ['Articles', '/blog'], ['Contact', '/contact'],
        ];
        foreach ($header as $i => [$label, $url]) {
            Menu::query()->create(['label' => $label, 'url' => $url, 'location' => 'header', 'sort_order' => $i]);
        }
        Menu::query()->create(['label' => 'Privacy', 'url' => '/privacy', 'location' => 'footer']);

        Page::query()->create([
            'title' => 'Privacy',
            'slug' => 'privacy',
            'body' => '<p>Sample privacy page. Replace before launch.</p>',
        ]);

        Service::query()->create([
            'title' => 'ERP advisory',
            'summary' => 'Sample: selecting, implementing and rescuing ERP projects.',
            'is_published' => false,
        ]);

        Testimonial::query()->create([
            'name' => 'Sample Colleague',
            'position' => 'Director',
            'company' => 'Sample Company',
            'quote' => 'Sample quote. Replace with a real, approved testimonial.',
            'is_approved' => false,
        ]);
    }
}
