<?php

use App\Enums\PostStatus;
use App\Enums\ProjectStatus;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Skill;
use App\Models\Testimonial;

it('only returns live posts: published or scheduled and already due', function () {
    Post::factory()->create(['title' => 'Live']);
    Post::factory()->create(['title' => 'Draft', 'status' => PostStatus::Draft, 'published_at' => null]);
    Post::factory()->create(['title' => 'Due', 'status' => PostStatus::Scheduled, 'published_at' => now()->subHour()]);
    Post::factory()->create(['title' => 'Future', 'status' => PostStatus::Scheduled, 'published_at' => now()->addDay()]);
    Post::factory()->create(['title' => 'Future published', 'published_at' => now()->addDay()]);

    expect(Post::published()->pluck('title')->sort()->values()->all())->toBe(['Due', 'Live']);
});

it('only returns published projects and can narrow to featured', function () {
    Project::factory()->create(['title' => 'A', 'is_featured' => true]);
    Project::factory()->create(['title' => 'B']);
    Project::factory()->create(['title' => 'C', 'status' => ProjectStatus::Draft, 'is_featured' => true]);
    Project::factory()->create(['title' => 'D', 'status' => ProjectStatus::Archived]);

    expect(Project::published()->count())->toBe(2)
        ->and(Project::published()->featured()->pluck('title')->all())->toBe(['A']);
});

it('hides unpublished flag-based content', function () {
    Service::factory()->create(['is_published' => false]);
    Service::factory()->create();
    Experience::factory()->create(['is_published' => false]);

    expect(Service::published()->count())->toBe(1)
        ->and(Experience::published()->count())->toBe(0);
});

it('only shows approved testimonials', function () {
    Testimonial::factory()->create(['is_approved' => false]);
    Testimonial::factory()->create();

    expect(Testimonial::published()->count())->toBe(1);
});

it('orders by sort_order then id', function () {
    Service::factory()->create(['title' => 'Second', 'sort_order' => 2]);
    Service::factory()->create(['title' => 'First', 'sort_order' => 1]);
    Service::factory()->create(['title' => 'Also first', 'sort_order' => 1]);

    expect(Service::ordered()->pluck('title')->all())->toBe(['First', 'Also first', 'Second']);
});

it('orders the career timeline current role first, then newest', function () {
    Experience::factory()->create(['company' => 'Old', 'start_date' => '2005-01-01', 'is_current' => false]);
    Experience::factory()->create(['company' => 'Newer', 'start_date' => '2012-01-01', 'is_current' => false]);
    Experience::factory()->create(['company' => 'Now', 'start_date' => '2018-01-01', 'is_current' => true]);

    expect(Experience::ordered()->pluck('company')->all())->toBe(['Now', 'Newer', 'Old']);
});

it('loads a skill group with its ordered skills', function () {
    $skill = Skill::factory()->create(['sort_order' => 2]);
    Skill::factory()->create(['skill_group_id' => $skill->skill_group_id, 'sort_order' => 1, 'name' => 'first']);

    expect($skill->group->skills->first()->name)->toBe('first');
});
