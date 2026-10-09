<?php

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

it('generates webp thumb and large conversions for project images', function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);

    $project = Project::factory()->create();
    $path = tempnam(sys_get_temp_dir(), 'img').'.png';
    imagepng(imagecreatetruecolor(1600, 900), $path);

    $media = $project->addMedia($path)
        ->toMediaCollection('gallery');

    $media = $media->fresh();

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('large'))->toBeTrue()
        ->and($media->getUrl('thumb'))->toEndWith('.webp');
});
