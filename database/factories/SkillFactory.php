<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Skill> */
class SkillFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'skill_group_id' => SkillGroup::factory(),
            'name' => fake()->unique()->word(),
            'level' => fake()->numberBetween(1, 5),
            'years' => fake()->numberBetween(1, 20),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
