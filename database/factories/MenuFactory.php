<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Menu> */
class MenuFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'url' => '/'.fake()->slug(1),
            'location' => 'header',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
