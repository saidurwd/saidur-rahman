<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

function validProject(array $overrides = []): array
{
    return array_merge([
        'title' => 'HMS ERP',
        'status' => 'draft',
        'summary' => 'A hospital system.',
        'problem' => '<p>Problem</p>',
        'tech_stack' => 'Laravel, MySQL, Laravel',
    ], $overrides);
}

function tinyPng(string $name = 'cover.png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'img').'.png';
    imagepng(imagecreatetruecolor(800, 450), $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

describe('access', function () {
    it('keeps guests out of every projects route', function () {
        auth()->logout();
        $project = Project::factory()->create();

        foreach ([
            ['get', '/admin/projects'], ['get', '/admin/projects/create'], ['post', '/admin/projects'],
            ['get', "/admin/projects/{$project->id}/edit"], ['put', "/admin/projects/{$project->id}"],
            ['delete', "/admin/projects/{$project->id}"], ['get', '/admin/projects/trash'],
            ['post', '/admin/projects/reorder'], ['post', '/admin/projects/bulk'],
        ] as [$method, $url]) {
            $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
        }
    });

    it('refuses a signed-in user who has not set up two-factor', function () {
        $user = User::factory()->create();
        $this->flushSession()->actingAs($user)->get('/admin/projects')->assertRedirect('/admin/two-factor');
    });
});

describe('index', function () {
    it('lists projects in order with pagination', function () {
        Project::factory()->count(20)->sequence(fn ($s) => ['title' => 'Project '.str_pad($s->index, 2, '0', STR_PAD_LEFT), 'sort_order' => $s->index])->create();

        $this->get('/admin/projects')->assertOk()->assertSee('Project 00')->assertDontSee('Project 17');
        $this->get('/admin/projects?page=2')->assertOk()->assertSee('Project 17');
    });

    it('searches by title and client', function () {
        Project::factory()->create(['title' => 'Alpha ERP', 'client' => 'Acme']);
        Project::factory()->create(['title' => 'Beta HMS', 'client' => 'Globex']);

        $this->get('/admin/projects?q=alpha')->assertSee('Alpha ERP')->assertDontSee('Beta HMS');
        $this->get('/admin/projects?q=globex')->assertSee('Beta HMS')->assertDontSee('Alpha ERP');
    });

    it('filters by status', function () {
        Project::factory()->create(['title' => 'Live one']);
        Project::factory()->create(['title' => 'Draft one', 'status' => ProjectStatus::Draft]);

        $this->get('/admin/projects?status=draft')->assertSee('Draft one')->assertDontSee('Live one');
    });

    it('only allows drag reordering when not filtered', function () {
        Project::factory()->create();

        $this->get('/admin/projects')->assertSee('data-sortable', false);
        $this->get('/admin/projects?q=x')->assertDontSee('data-sortable', false);
    });

    it('hides trashed projects and links to the trash', function () {
        Project::factory()->create(['title' => 'Gone'])->delete();

        $this->get('/admin/projects')->assertDontSee('Gone')->assertSee('Trash (1)');
    });
});

describe('create', function () {
    it('renders the form', function () {
        $this->get('/admin/projects/create')->assertOk()->assertSee('New project')->assertSee('data-editor="rich"', false);
    });

    it('creates a project with a generated slug, clean tech stack and next sort order', function () {
        Project::factory()->create(['sort_order' => 4]);

        $response = $this->post('/admin/projects', validProject());

        $project = Project::where('title', 'HMS ERP')->firstOrFail();
        $response->assertRedirect("/admin/projects/{$project->id}/edit");
        expect($project->slug)->toBe('hms-erp')
            ->and($project->tech_stack)->toBe(['Laravel', 'MySQL'])
            ->and($project->sort_order)->toBe(5)
            ->and($project->status)->toBe(ProjectStatus::Draft);
    });

    it('validates the input', function () {
        $this->post('/admin/projects', ['title' => '', 'status' => 'nope', 'live_url' => 'javascript:alert(1)', 'slug' => 'Bad Slug'])
            ->assertSessionHasErrors(['title', 'status', 'live_url', 'slug']);
        expect(Project::count())->toBe(0);
    });

    it('rejects a duplicate slug, even one from a trashed project', function () {
        Project::factory()->create(['slug' => 'taken'])->delete();

        $this->post('/admin/projects', validProject(['slug' => 'taken']))->assertSessionHasErrors('slug');
    });

    it('purifies rich text so scripts and handlers never reach the database', function () {
        $this->post('/admin/projects', validProject([
            'problem' => '<p onclick="x()">Hello</p><script>alert(1)</script><img src="javascript:alert(1)"><a href="javascript:evil()">bad</a><a href="https://ok.test">ok</a>',
            'summary' => '<b>Bold</b> summary<script>x</script>',
        ]))->assertRedirect();

        $project = Project::firstOrFail();
        expect($project->problem)
            ->toContain('Hello')
            ->toContain('https://ok.test')
            ->not->toContain('script')
            ->not->toContain('onclick')
            ->not->toContain('javascript:')
            ->and($project->summary)->not->toContain('<');
    });

    it('stores the cover and gallery with alt text and generates webp conversions', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);

        $this->post('/admin/projects', validProject([
            'cover' => tinyPng(), 'cover_alt' => 'The dashboard',
            'gallery' => [tinyPng('a.png'), tinyPng('b.png')],
        ]))->assertRedirect();

        $project = Project::firstOrFail();
        expect($project->getMedia('cover'))->toHaveCount(1)
            ->and($project->getFirstMedia('cover')->getCustomProperty('alt'))->toBe('The dashboard')
            ->and($project->getMedia('gallery'))->toHaveCount(2)
            ->and($project->getFirstMedia('cover')->fresh()->hasGeneratedConversion('thumb'))->toBeTrue();
    });

    it('rejects non-images and oversized uploads', function () {
        $this->post('/admin/projects', validProject([
            'cover' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            'gallery' => [UploadedFile::fake()->create('big.png', 6000, 'image/png')],
        ]))->assertSessionHasErrors(['cover', 'gallery.0']);
    });
});

describe('update', function () {
    it('shows the edit form with a view-on-site link for published projects', function () {
        $project = Project::factory()->create(['title' => 'Live', 'slug' => 'live']);

        $this->get("/admin/projects/{$project->id}/edit")->assertOk()->assertSee('Edit project')->assertSee('View on site')->assertSee('/projects/live');
    });

    it('updates the fields and keeps the slug when it is left unchanged', function () {
        $project = Project::factory()->create(['title' => 'Old', 'slug' => 'old']);

        $this->put("/admin/projects/{$project->id}", validProject(['title' => 'New title', 'slug' => 'old']))->assertRedirect();

        expect($project->fresh()->title)->toBe('New title')
            ->and($project->fresh()->slug)->toBe('old')
            ->and(Redirect::count())->toBe(0);
    });

    it('writes a 301 redirect when the slug changes and avoids chains', function () {
        $project = Project::factory()->create(['slug' => 'first']);

        $this->put("/admin/projects/{$project->id}", validProject(['slug' => 'second']))->assertRedirect();
        $this->put("/admin/projects/{$project->id}", validProject(['slug' => 'third']))->assertRedirect();

        expect(Redirect::query()->orderBy('from_path')->get()->map(fn ($r) => [$r->from_path, $r->to_path, $r->code])->all())
            ->toBe([['/projects/first', '/projects/third', 301], ['/projects/second', '/projects/third', 301]]);
    });

    it('serves recorded redirects for missing paths', function () {
        Redirect::create(['from_path' => '/projects/old-name', 'to_path' => '/projects/new-name', 'code' => 301]);

        $this->get('/projects/old-name')->assertStatus(301)->assertRedirect('/projects/new-name');
        $this->get('/projects/not-there')->assertNotFound();
    });

    it('can keep its own slug without a uniqueness error', function () {
        $project = Project::factory()->create(['slug' => 'mine']);

        $this->put("/admin/projects/{$project->id}", validProject(['slug' => 'mine']))->assertSessionHasNoErrors();
    });

    it('removes the cover and gallery images on request', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $project = Project::factory()->create();
        $project->addMedia(tinyPng())->toMediaCollection('cover');
        $image = $project->addMedia(tinyPng())->toMediaCollection('gallery');

        $this->put("/admin/projects/{$project->id}", validProject(['remove_cover' => 1, 'remove_media' => [$image->id]]))->assertRedirect();

        expect($project->fresh()->getMedia('cover'))->toHaveCount(0)
            ->and($project->fresh()->getMedia('gallery'))->toHaveCount(0);
    });

    it('records the change in the activity log', function () {
        $project = Project::factory()->create();
        Activity::query()->delete();

        $this->put("/admin/projects/{$project->id}", validProject(['title' => 'Changed']))->assertRedirect();

        expect(Activity::query()->where('subject_id', $project->id)->where('description', 'updated')->exists())->toBeTrue();
    });
});

describe('trash', function () {
    it('soft deletes, lists in trash, restores and force deletes', function () {
        $project = Project::factory()->create(['title' => 'Temp']);

        $this->delete("/admin/projects/{$project->id}")->assertRedirect('/admin/projects');
        expect(Project::find($project->id))->toBeNull();

        $this->get('/admin/projects/trash')->assertOk()->assertSee('Temp');

        $this->post("/admin/projects/{$project->id}/restore")->assertRedirect('/admin/projects/trash');
        expect(Project::find($project->id))->not->toBeNull();

        $project->delete();
        $this->delete("/admin/projects/{$project->id}/force")->assertRedirect();
        expect(Project::withTrashed()->find($project->id))->toBeNull();
    });

    it('removes media when force deleting', function () {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $project = Project::factory()->create();
        $project->addMedia(tinyPng())->toMediaCollection('gallery');
        $project->delete();

        $this->delete("/admin/projects/{$project->id}/force");

        expect(Storage::disk('public')->allFiles())->toBe([]);
    });
});

describe('reorder and bulk', function () {
    it('reorders projects', function () {
        [$a, $b, $c] = Project::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create()->all();

        $this->postJson('/admin/projects/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

        expect(Project::ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
    });

    it('reordering one page does not disturb the rest of the list', function () {
        $projects = Project::factory()->count(4)->sequence(fn ($s) => ['sort_order' => ($s->index + 1) * 10])->create();
        [$a, $b, $c, $d] = $projects->all();

        $this->postJson('/admin/projects/reorder', ['ids' => [$b->id, $a->id]])->assertOk();

        expect(Project::ordered()->pluck('id')->all())->toBe([$b->id, $a->id, $c->id, $d->id]);
    });

    it('validates the reorder payload', function () {
        $this->postJson('/admin/projects/reorder', ['ids' => ['x']])->assertStatus(422);
    });

    it('bulk publishes, unpublishes and trashes', function () {
        $projects = Project::factory()->count(2)->create(['status' => ProjectStatus::Draft]);
        $ids = $projects->pluck('id')->all();

        $this->post('/admin/projects/bulk', ['action' => 'publish', 'ids' => $ids])->assertRedirect();
        expect(Project::published()->count())->toBe(2);

        $this->post('/admin/projects/bulk', ['action' => 'unpublish', 'ids' => $ids])->assertRedirect();
        expect(Project::published()->count())->toBe(0);

        $this->post('/admin/projects/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect();
        expect(Project::count())->toBe(0)->and(Project::onlyTrashed()->count())->toBe(2);
    });

    it('rejects unknown bulk actions and empty selections', function () {
        $this->post('/admin/projects/bulk', ['action' => 'explode', 'ids' => [1]])->assertSessionHasErrors('action');
        $this->post('/admin/projects/bulk', ['action' => 'publish'])->assertSessionHasErrors('ids');
    });
});
