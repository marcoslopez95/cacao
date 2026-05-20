<?php

namespace Database\Factories;

use App\Enums\EducationalLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guardian_id' => null,
            'educational_level' => EducationalLevel::University,
            'current_pensum_id' => null,
            'academic_year' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Student $student) {
            $role = Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
            $student->user->assignRole($role);
        });
    }

    public function primary(): static
    {
        return $this->state([
            'educational_level' => EducationalLevel::Primary,
            'academic_year' => fake()->numberBetween(1, 6),
        ]);
    }

    public function secondary(): static
    {
        return $this->state([
            'educational_level' => EducationalLevel::Secondary,
            'academic_year' => fake()->numberBetween(1, 5),
        ]);
    }

    public function withGuardian(): static
    {
        return $this->state(fn () => [
            'guardian_id' => GuardianFactory::new(),
        ]);
    }
}
