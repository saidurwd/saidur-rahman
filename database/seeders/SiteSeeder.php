<?php

namespace Database\Seeders;

use App\Models\Expertise;
use App\Models\Menu;
use App\Models\Page;
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
            'body' => <<<'HTML'
<p>This is a short, plain-language summary of what this website does with information about you. Please review and adjust it to match how the site is actually run before launch.</p>
<h2>What is collected</h2>
<ul>
<li><strong>Contact form.</strong> If you write to me, I receive the name, email address, company, subject and message you enter, plus your IP address and browser details, which are used to filter spam. Messages are kept until I delete them.</li>
<li><strong>Visits.</strong> I count page views without cookies. For each visit I store the page, the country (when available), the website you came from, and whether you used a phone, tablet or computer. Your IP address is never stored: it is mixed into a one-way code that only identifies "the same visitor today" and cannot be followed across days. If your browser sends Do Not Track or Global Privacy Control, nothing is recorded.</li>
<li><strong>CV downloads.</strong> I record which version was downloaded, the country and the website you came from, in the same anonymous way.</li>
</ul>
<h2>Cookies</h2>
<p>Visitors get no cookies. The contact page sets a technical cookie that makes the form work, and it is deleted when you close your browser.</p>
<h2>Other services</h2>
<p>The contact form uses Cloudflare Turnstile to tell people from bots, and the site is served through Cloudflare. Email is sent through my email provider. These services process data under their own policies.</p>
<h2>Your choices</h2>
<p>You can ask me to show or delete anything you sent me by emailing the address on the contact page.</p>
HTML,
        ]);

        Expertise::query()->create([
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
