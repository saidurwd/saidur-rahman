<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sitemap:generate')]
#[Description('Write public/sitemap.xml from the published content')]
class GenerateSitemap extends Command
{
    public function handle(SitemapGenerator $sitemap): int
    {
        $path = $sitemap->write();

        $this->info('Sitemap written to '.$path.' ('.count($sitemap->entries()).' URLs).');

        return self::SUCCESS;
    }
}
