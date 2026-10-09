<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
class ExperienceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'role' => fake()->jobTitle(),
            'location' => fake()->city(),
            'employment_type' => 'Full-time',
            'start_date' => fake()->dateTimeBetween('-15 years', '-3 years'),
            'end_date' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'is_current' => false,
            'summary' => fake()->paragraph(),
            'achievements' => [fake()->sentence(), fake()->sentence()],
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
