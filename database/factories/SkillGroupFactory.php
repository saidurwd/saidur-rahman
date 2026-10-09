<?php

namespace Database\Factories;

use App\Models\SkillGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SkillGroup> */
class SkillGroupFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
