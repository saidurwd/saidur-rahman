<?php

use App\Enums\ProjectStatus;
use App\Models\Certification;
use App\Models\CvFile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Expertise;
use App\Models\Page;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SkillGroup;
use App\Models\Testimonial;
use App\Services\CacheService;
use App\Services\SettingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function jsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return array_map(fn ($json) => json_decode($json, true), $matches[1]);
}

describe('home', function () {
    it('renders from the seeded content', function () {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('I lead IT and I still build the systems.')
            ->assertSee('View work')
            ->assertSee('Years in enterprise IT')
            ->assertSee('HMS ERP')
            ->assertSee('Tea ERP')
            ->assertSee('Head of IT')
            ->assertSee('Open to senior IT leadership roles in Qatar and the GCC');
    });

    it('still renders with an empty database', function () {
        $this->get('/')->assertOk()->assertSee('View work');
    });

    it('hides unpublished, draft, archived and trashed content', function () {
        Project::factory()->create(['title' => 'Public project', 'is_featured' => true]);
        Project::factory()->create(['title' => 'Draft project', 'status' => ProjectStatus::Draft, 'is_featured' => true]);
        Project::factory()->create(['title' => 'Archived project', 'status' => ProjectStatus::Archived, 'is_featured' => true]);
        Project::factory()->create(['title' => 'Trashed project', 'is_featured' => true])->delete();
        Experience::factory()->create(['role' => 'Visible role']);
        Experience::factory()->create(['role' => 'Hidden role', 'is_published' => false]);

        $this->get('/')
            ->assertSee('Public project')->assertSee('Visible role')
            ->assertDontSee('Draft project')->assertDontSee('Archived project')->assertDontSee('Trashed project')->assertDontSee('Hidden role');
    });

    it('shows featured projects, falling back to the first published ones', function () {
        Project::factory()->create(['title' => 'Featured one', 'is_featured' => true, 'sort_order' => 2]);
        Project::factory()->create(['title' => 'Plain one', 'is_featured' => false, 'sort_order' => 1]);

        $this->get('/')->assertSee('Featured one')->assertDontSee('Plain one');

        Project::where('title', 'Featured one')->update(['is_featured' => false]);
        app(CacheService::class)->flush();

        $this->get('/')->assertSee('Featured one')->assertSee('Plain one');
    });

    it('shows the numbers strip only when figures are set, and counts them up', function () {
        $this->get('/')->assertDontSee('Key figures');

        app(SettingService::class)->set('stats', [['value' => '21+', 'label' => 'Years in IT'], ['value' => '3', 'label' => 'Flagship systems']]);

        $this->get('/')->assertSee('Key figures')->assertSee('Years in IT')->assertSee('Flagship systems')->assertSee("countUp('21+')", false);
    });

    it('offers the CV download only when an active CV exists', function () {
        $this->get('/')->assertDontSee('Download CV');

        CvFile::factory()->create(['is_active' => false]);
        app(CacheService::class)->flush();
        $this->get('/')->assertDontSee('Download CV');

        CvFile::factory()->create(['is_active' => true]);
        $this->get('/')->assertSee('Download CV');
    });

    it('describes the person with JSON-LD', function () {
        app(SettingService::class)->setMany([
            'site_name' => 'Saidur Rahman', 'headline' => 'Head of IT', 'location' => 'Dhaka',
            'linkedin' => 'https://www.linkedin.com/in/example', 'contact_email' => 'hello@example.com',
        ]);

        $person = jsonLd($this->get('/')->getContent())[0];

        expect($person)->toMatchArray(['@type' => 'Person', 'name' => 'Saidur Rahman', 'jobTitle' => 'Head of IT'])
            ->and($person['sameAs'])->toBe(['https://www.linkedin.com/in/example'])
            ->and($person['address']['addressLocality'])->toBe('Dhaka');
    });

    it('uses the portrait from settings and a drawn diagram otherwise', function () {
        $this->get('/')->assertSee('class="draw', false)->assertDontSee('Portrait of');

        app(SettingService::class)->set('profile_photo', 'settings/profile_photo/me.webp');

        $this->get('/')->assertSee('Portrait of')->assertSee('settings/profile_photo/me.webp');
    });

    it('links to contact by email until the contact page exists', function () {
        app(SettingService::class)->set('contact_email', 'hello@example.com');

        $this->get('/')->assertSee('mailto:hello@example.com', false);
    });
});

describe('about', function () {
    it('shows story, philosophy, skills, certifications and education', function () {
        app(SettingService::class)->setMany(['bio' => "First paragraph.\n\nSecond paragraph.", 'philosophy' => 'Simple systems outlast clever ones.']);
        $group = SkillGroup::factory()->create(['name' => 'Enterprise systems']);
        Skill::factory()->create(['skill_group_id' => $group->id, 'name' => 'ERP', 'years' => 12]);
        Skill::factory()->create(['skill_group_id' => $group->id, 'name' => 'Hidden skill', 'is_published' => false]);
        SkillGroup::factory()->create(['name' => 'Empty group']);
        Certification::factory()->create(['name' => 'Sample cert']);
        Education::factory()->create(['degree' => 'MSc']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('First paragraph.')->assertSee('Second paragraph.')
            ->assertSee('Simple systems outlast clever ones.')
            ->assertSee('Enterprise systems')->assertSee('ERP')->assertSee('12y')
            ->assertDontSee('Hidden skill')->assertDontSee('Empty group')
            ->assertSee('Sample cert')->assertSee('MSc');
    });

    it('has Person and breadcrumb JSON-LD', function () {
        $types = collect(jsonLd($this->get('/about')->getContent()))->pluck('@type')->all();

        expect($types)->toBe(['Person', 'BreadcrumbList']);
    });

    it('shows no percentage bars for skills', function () {
        Skill::factory()->create();

        $this->get('/about')->assertDontSee('progress')->assertDontSee('%');
    });
});

describe('experience', function () {
    it('lists published roles, current first, with achievements', function () {
        Experience::factory()->create(['role' => 'Older role', 'start_date' => '2005-01-01', 'is_current' => false, 'end_date' => '2010-01-01']);
        Experience::factory()->create(['role' => 'Current role', 'start_date' => '2018-01-01', 'is_current' => true, 'end_date' => null, 'achievements' => ['Shipped the ERP']]);
        Experience::factory()->create(['role' => 'Hidden role', 'is_published' => false]);

        $this->get('/experience')
            ->assertOk()
            ->assertSeeInOrder(['Current role', 'Older role'])
            ->assertSee('Shipped the ERP')
            ->assertSee('Present')
            ->assertDontSee('Hidden role');
    });

    it('renders an empty state', function () {
        $this->get('/experience')->assertOk()->assertSee('Experience will be added soon.');
    });
});

describe('projects', function () {
    it('lists published case studies only', function () {
        Project::factory()->create(['title' => 'Shown']);
        Project::factory()->create(['title' => 'Draft', 'status' => ProjectStatus::Draft]);
        Project::factory()->create(['title' => 'Gone'])->delete();

        $this->get('/projects')->assertOk()->assertSee('Shown')->assertDontSee('Draft')->assertDontSee('Gone');
    });

    it('filters by technology, ignoring case', function () {
        Project::factory()->create(['title' => 'Laravel one', 'tech_stack' => ['Laravel', 'MySQL']]);
        Project::factory()->create(['title' => 'Go one', 'tech_stack' => ['Go']]);

        $this->get('/projects')->assertSee('Laravel one')->assertSee('Go one')->assertSee('Filter by technology');
        $this->get('/projects?tech=laravel')->assertSee('Laravel one')->assertDontSee('Go one');
        $this->get('/projects?tech=Go')->assertSee('Go one')->assertDontSee('Laravel one');
    });

    it('renders an empty state', function () {
        $this->get('/projects')->assertOk()->assertSee('Case studies are coming soon.');
    });
});

describe('case study', function () {
    it('renders the sections, details and breadcrumb JSON-LD', function () {
        $project = Project::factory()->create([
            'title' => 'HMS ERP', 'slug' => 'hms-erp', 'client' => 'Acme Hospital', 'role' => 'Lead architect',
            'problem' => '<p>The problem text</p>', 'solution' => '<p>The solution text</p>',
            'architecture' => null, 'outcomes' => '<p>The outcome text</p>',
            'tech_stack' => ['Laravel'], 'live_url' => 'https://example.com',
        ]);

        $response = $this->get('/projects/hms-erp')
            ->assertOk()
            ->assertSee('Context and challenge')->assertSee('The problem text')
            ->assertSee('My role and solution')->assertSee('The solution text')
            ->assertSee('Outcome')->assertSee('The outcome text')
            ->assertDontSee('Architecture</h2>', false)
            ->assertSee('Acme Hospital')->assertSee('Lead architect')->assertSee('Laravel')
            ->assertSee('https://example.com');

        $crumbs = collect(jsonLd($response->getContent()))->firstWhere('@type', 'BreadcrumbList');
        expect(collect($crumbs['itemListElement'])->pluck('name')->all())->toBe(['Home', 'Projects', 'HMS ERP']);
    });

    it('uses the SEO fields for the title and description', function () {
        Project::factory()->create(['slug' => 'seo', 'title' => 'Plain title', 'summary' => 'Plain summary', 'seo_title' => 'Custom SEO title', 'seo_description' => 'Custom SEO description']);

        $this->get('/projects/seo')->assertSee('<title>Custom SEO title ·', false)->assertSee('content="Custom SEO description"', false);
    });

    it('returns 404 for drafts, archived, trashed and unknown slugs', function () {
        Project::factory()->create(['slug' => 'draft', 'status' => ProjectStatus::Draft]);
        Project::factory()->create(['slug' => 'archived', 'status' => ProjectStatus::Archived]);
        Project::factory()->create(['slug' => 'trashed'])->delete();

        foreach (['draft', 'archived', 'trashed', 'missing'] as $slug) {
            $this->get("/projects/{$slug}")->assertNotFound();
        }
    });

    it('links to the previous and next case study', function () {
        Project::factory()->create(['title' => 'First', 'slug' => 'first', 'sort_order' => 1]);
        Project::factory()->create(['title' => 'Second', 'slug' => 'second', 'sort_order' => 2]);
        Project::factory()->create(['title' => 'Third', 'slug' => 'third', 'sort_order' => 3]);

        $this->get('/projects/second')->assertSee('/projects/first', false)->assertSee('/projects/third', false);
        $this->get('/projects/first')->assertDontSee('← Previous')->assertSee('Next →');
        $this->get('/projects/third')->assertSee('← Previous')->assertDontSee('Next →');
    });

    it('prints stored rich text without escaping it', function () {
        Project::factory()->create(['slug' => 'rich', 'problem' => '<h3>Heading</h3><ul><li>Point</li></ul>']);

        $this->get('/projects/rich')->assertSee('<h3>Heading</h3>', false)->assertSee('<li>Point</li>', false);
    });

    it('serves responsive images with reserved space', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $project = Project::factory()->create(['slug' => 'pics']);
        $path = tempnam(sys_get_temp_dir(), 'img').'.png';
        imagepng(imagecreatetruecolor(2000, 1000), $path);
        $project->addMedia($path)->toMediaCollection('cover');
        app(CacheService::class)->flush();

        $media = $project->fresh()->getFirstMedia('cover');
        expect($media->getCustomProperty('width'))->toBe(2000)->and($media->getCustomProperty('height'))->toBe(1000);

        $html = $this->get('/projects/pics')->getContent();
        expect($html)->toContain('srcset=')->toContain('480w')->toContain('1400w')
            ->toContain('width="1400"')->toContain('height="700"')->toContain('fetchpriority="high"');
    });

    it('picks up admin edits straight away', function () {
        Project::factory()->create(['slug' => 'live', 'title' => 'Before']);
        $this->get('/projects/live')->assertSee('Before');

        Project::where('slug', 'live')->first()->update(['title' => 'After']);

        $this->get('/projects/live')->assertSee('After')->assertDontSee('Before');
    });
});

describe('expertise and pages', function () {
    it('lists and shows published expertise only', function () {
        Expertise::factory()->create(['title' => 'ERP advisory', 'slug' => 'erp-advisory', 'body' => '<p>Details here</p>']);
        Expertise::factory()->create(['title' => 'Hidden expertise', 'slug' => 'hidden', 'is_published' => false]);

        $this->get('/expertise')->assertOk()->assertSee('ERP advisory')->assertDontSee('Hidden expertise');
        $this->get('/expertise/erp-advisory')->assertOk()->assertSee('Details here');
        $this->get('/expertise/hidden')->assertNotFound();
    });

    it('redirects the old /services addresses to /expertise', function () {
        $this->get('/services')->assertRedirect('/expertise')->assertStatus(301);
        $this->get('/services/erp-advisory')->assertRedirect('/expertise/erp-advisory')->assertStatus(301);
    });

    it('serves editable pages by slug and 404s unpublished ones', function () {
        Page::factory()->create(['title' => 'Privacy', 'slug' => 'privacy', 'body' => '<p>We keep little data.</p>']);
        Page::factory()->create(['title' => 'Secret', 'slug' => 'secret', 'is_published' => false]);

        $this->get('/privacy')->assertOk()->assertSee('We keep little data.');
        $this->get('/secret')->assertNotFound();
        $this->get('/does-not-exist')->assertNotFound();
    });

    it('does not let pages shadow the admin or other routes', function () {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/about')->assertOk();
        $this->get('/up')->assertOk();
    });

    it('does not run pages for non-GET requests or odd paths', function () {
        Page::factory()->create(['slug' => 'privacy']);

        $this->post('/privacy')->assertStatus(405);
        $this->get('/Privacy')->assertNotFound();
        $this->get('/privacy/extra')->assertNotFound();
    });
});

describe('errors', function () {
    it('renders a styled 404 with a way back', function () {
        $this->get('/nowhere')
            ->assertNotFound()
            ->assertSee('This page is not on the map.')
            ->assertSee('noindex', false)
            ->assertSee('data-site-header', false);
    });

    it('renders a self-contained 500 page that needs no database or build', function () {
        $html = view('errors.500')->render();

        expect($html)->toContain('Something went wrong on my side')
            ->toContain('Error 500')
            ->not->toContain('/build/assets')
            ->not->toContain('data-site-header');
    });

    it('has pages for the other common statuses', function () {
        foreach ([403, 419, 429, 503] as $status) {
            expect(view("errors.{$status}")->render())->toContain("Error {$status}");
        }
    });
});

describe('seo and accessibility basics', function () {
    it('gives every public page a title, description, canonical and a main landmark', function (string $path) {
        $this->seed(DatabaseSeeder::class);

        $this->get($path)
            ->assertOk()
            ->assertSee('<title>', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('<main id="main"', false)
            ->assertSee('<h1', false);
    })->with(['/', '/about', '/experience', '/projects', '/projects/hms-erp']);

    it('has exactly one h1 per page', function (string $path) {
        $this->seed(DatabaseSeeder::class);

        expect(substr_count($this->get($path)->getContent(), '<h1'))->toBe(1);
    })->with(['/', '/about', '/experience', '/projects', '/projects/hms-erp']);
});

describe('testimonials', function () {
    it('shows approved testimonials on the home page and the about page', function () {
        Testimonial::factory()->create(['name' => 'Jane Doe', 'position' => 'CIO', 'company' => 'Acme', 'quote' => 'A rare leader who still ships.']);

        foreach (['/', '/about'] as $path) {
            $this->get($path)->assertOk()->assertSee('What colleagues and clients say')->assertSee('A rare leader who still ships.')->assertSee('Jane Doe')->assertSee('CIO, Acme')
                ->assertSee('<blockquote', false)->assertSee('<figcaption', false)->assertSee('>JD<', false);
        }
    });

    it('hides unapproved and trashed ones, and the whole section when there are none', function () {
        Testimonial::factory()->create(['name' => 'Pending Pat', 'is_approved' => false]);
        Testimonial::factory()->create(['name' => 'Deleted Dan'])->delete();

        $this->get('/')->assertDontSee('Pending Pat')->assertDontSee('Deleted Dan')->assertDontSee('What colleagues and clients say');
        $this->get('/about')->assertDontSee('What colleagues and clients say');
    });

    it('limits the home page to three, in the admin order, and shows all on the about page', function () {
        Testimonial::factory()->count(4)->sequence(fn ($s) => ['name' => 'Person '.$s->index, 'sort_order' => $s->index])->create();

        $this->get('/')->assertSee('Person 0')->assertSee('Person 2')->assertDontSee('Person 3');
        $this->get('/about')->assertSee('Person 3');
    });

    it('escapes the quote text and shows the photo when there is one', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $testimonial = Testimonial::factory()->create(['quote' => '<script>alert(1)</script> Great.']);
        $path = tempnam(sys_get_temp_dir(), 'p').'.png';
        imagepng(imagecreatetruecolor(300, 300), $path);
        $testimonial->addMedia($path)->toMediaCollection('photo');
        app(CacheService::class)->flush();

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false)->assertSee('rounded-full border border-line object-cover', false)->assertDontSee('aria-hidden="true">'.'TF');
    });

    it('updates straight away when approved in the admin', function () {
        $testimonial = Testimonial::factory()->create(['name' => 'Late Larry', 'is_approved' => false]);
        $this->get('/')->assertDontSee('Late Larry');

        $testimonial->update(['is_approved' => true]);

        $this->get('/')->assertSee('Late Larry');
    });
});
