<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Education> */
class EducationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'institution' => fake()->company().' University',
            'degree' => 'BSc',
            'field' => 'Computer Science',
            'start_year' => 2000,
            'end_year' => 2004,
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
