<?php

namespace Database\Factories;

use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Certification> */
class CertificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'issuer' => fake()->company(),
            'issued_at' => fake()->date(),
            'expires_at' => null,
            'credential_url' => fake()->url(),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
