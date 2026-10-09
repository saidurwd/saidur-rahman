<?php

use App\Models\Project;
use App\Services\CacheService;

it('serves cached values until content changes', function () {
    $cache = app(CacheService::class);
    $calls = 0;
    $remember = function () use ($cache, &$calls) {
        return $cache->remember(CacheService::CONTENT, 'projects', null, function () use (&$calls) {
            $calls++;

            return Project::published()->count();
        });
    };

    expect($remember())->toBe(0)
        ->and($remember())->toBe(0)
        ->and($calls)->toBe(1);

    Project::factory()->create();

    expect($remember())->toBe(1)
        ->and($calls)->toBe(2);
});

it('flushes content cache on update, delete and restore', function () {
    $cache = app(CacheService::class);
    $project = Project::factory()->create();
    $read = fn () => $cache->remember(CacheService::CONTENT, 'title', null, fn () => Project::withTrashed()->first()->title.Project::count());

    $before = $read();
    $project->update(['title' => 'Changed']);
    expect($read())->not->toBe($before);

    $afterUpdate = $read();
    $project->delete();
    expect($read())->not->toBe($afterUpdate);

    $afterDelete = $read();
    $project->restore();
    expect($read())->not->toBe($afterDelete);
});
