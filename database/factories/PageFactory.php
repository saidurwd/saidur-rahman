<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
