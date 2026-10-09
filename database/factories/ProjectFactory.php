<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'client' => fake()->company(),
            'role' => 'Lead',
            'period' => '2022 – 2024',
            'summary' => fake()->sentence(),
            'problem' => '<p>'.fake()->paragraph().'</p>',
            'solution' => '<p>'.fake()->paragraph().'</p>',
            'architecture' => '<p>'.fake()->paragraph().'</p>',
            'outcomes' => '<p>'.fake()->paragraph().'</p>',
            'tech_stack' => ['Laravel', 'MySQL'],
            'is_featured' => false,
            'status' => ProjectStatus::Published,
            'sort_order' => 0,
        ];
    }
}
