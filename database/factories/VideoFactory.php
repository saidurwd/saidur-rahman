<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Video> */
class VideoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'youtube_id' => Str::random(11),
            'description' => fake()->paragraph(),
            'topic' => null,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
            'sort_order' => 0,
        ];
    }
}
