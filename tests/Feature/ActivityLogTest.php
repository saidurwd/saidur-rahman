<?php

use App\Models\Project;
use Spatie\Activitylog\Models\Activity;

it('records content changes in the activity log', function () {
    $project = Project::factory()->create();
    $project->update(['summary' => 'New summary']);

    expect(Activity::query()->where('subject_type', Project::class)->count())->toBe(2);
});
