<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(5),
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'category_id' => Category::factory(),
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
            'reading_minutes' => 3,
            'sort_order' => 0,
        ];
    }
}
