<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'icon' => 'bi-gear',
            'summary' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
