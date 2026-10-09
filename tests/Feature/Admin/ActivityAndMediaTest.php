<?php

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    signInAsAdmin();
});

it('keeps guests out of the activity log and media pages', function () {
    auth()->logout();

    $this->flushSession()->get('/admin/activity')->assertRedirect('/admin/login');
    $this->flushSession()->get('/admin/media')->assertRedirect('/admin/login');
});

it('lists who changed what, with the changed fields', function () {
    $project = Project::factory()->create(['title' => 'Original']);
    $project->update(['title' => 'Renamed']);

    $this->get('/admin/activity')
        ->assertOk()
        ->assertSee('Project #'.$project->id)
        ->assertSee('Renamed')
        ->assertSee('updated');
});

it('records the signed-in admin as the cause', function () {
    $user = auth()->user();
    Project::factory()->create();

    expect(Activity::query()->latest('id')->first()->causer_id)->toBe($user->id);
    $this->get('/admin/activity')->assertSee($user->name);
});

it('filters the activity log by event and record type', function () {
    $project = Project::factory()->create(['title' => 'Filter me']);
    $project->update(['title' => 'Filtered']);

    $this->get('/admin/activity?event=created')->assertOk()
        ->assertViewHas('activities', fn ($page) => $page->pluck('event')->unique()->all() === ['created']);
    $this->get('/admin/activity?type='.urlencode(Project::class))->assertOk()->assertSee('Project');
    $this->get('/admin/activity?type='.urlencode('App\Models\Page'))->assertOk()->assertSee('No activity recorded yet.');
});

it('lists uploaded media with alt text warnings', function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);
    $project = Project::factory()->create(['title' => 'Has image']);
    $path = tempnam(sys_get_temp_dir(), 'img').'.png';
    imagepng(imagecreatetruecolor(400, 300), $path);
    $project->addMedia($path)->toMediaCollection('gallery');

    $this->get('/admin/media')->assertOk()->assertSee('Has image')->assertSee('no alt text');
    $this->get('/admin/media?type='.urlencode('App\Models\Testimonial'))->assertOk()->assertDontSee('Has image');
});

it('shows an empty media library', function () {
    $this->get('/admin/media')->assertOk()->assertSee('No images uploaded yet.');
});
