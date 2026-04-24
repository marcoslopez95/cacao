<?php

namespace Database\Factories;

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoordinationAssignment>
 */
class CoordinationAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coordination_id' => Coordination::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'ended_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
