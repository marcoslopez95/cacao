<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => 'Profesor',
            'token' => Str::uuid()->toString(),
            'invited_by' => User::factory(),
            'expires_at' => now()->addHours(48),
            'used_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subHour()]);
    }

    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()->subMinute()]);
    }
}
