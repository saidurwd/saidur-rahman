<?php

namespace Database\Factories;

use App\Models\CvFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CvFile> */
class CvFileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'label' => fake()->randomElement(['IT Leadership', 'Engineering']),
            'file_path' => 'cv/'.fake()->uuid().'.pdf',
            'is_active' => true,
            'downloads' => 0,
        ];
    }
}
