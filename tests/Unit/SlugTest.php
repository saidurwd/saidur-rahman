<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Tag;

it('generates a slug from the title on create', function () {
    $project = Project::factory()->create(['title' => 'HMS ERP System']);

    expect($project->slug)->toBe('hms-erp-system');
});

it('keeps slugs unique by suffixing a counter', function () {
    $first = Project::factory()->create(['title' => 'Tea ERP']);
    $second = Project::factory()->create(['title' => 'Tea ERP']);
    $third = Project::factory()->create(['title' => 'Tea ERP']);

    expect([$first->slug, $second->slug, $third->slug])->toBe(['tea-erp', 'tea-erp-2', 'tea-erp-3']);
});

it('does not reuse the slug of a soft-deleted record', function () {
    $first = Project::factory()->create(['title' => 'Tea ERP']);
    $first->delete();

    expect(Project::factory()->create(['title' => 'Tea ERP'])->slug)->toBe('tea-erp-2');
});

it('keeps an explicit slug and leaves it stable when the title changes', function () {
    $project = Project::factory()->create(['title' => 'Original', 'slug' => 'custom']);
    $project->update(['title' => 'Renamed']);

    expect($project->fresh()->slug)->toBe('custom');
});

it('slugs categories and tags from their name', function () {
    expect(Category::factory()->create(['name' => 'IT Leadership'])->slug)->toBe('it-leadership')
        ->and(Tag::factory()->create(['name' => 'Digital transformation'])->slug)->toBe('digital-transformation');
});
