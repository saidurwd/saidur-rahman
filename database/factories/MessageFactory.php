<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'status' => MessageStatus::New,
        ];
    }
}
