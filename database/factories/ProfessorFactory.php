<?php

namespace Database\Factories;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Professor>
 */
class ProfessorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'weekly_hour_limit' => fake()->numberBetween(10, 40),
            'active'            => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Professor $professor) {
            $role = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
            $professor->user->assignRole($role);
        });
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
