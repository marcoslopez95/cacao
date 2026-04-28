<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2026-06-01');

        return [
            'name'       => fake()->unique()->numerify('20##-#'),
            'type'       => PeriodType::Semester,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+6 months')->format('Y-m-d'),
            'status'     => PeriodStatus::Upcoming,
        ];
    }

    public function semester(): static
    {
        return $this->state(['type' => PeriodType::Semester]);
    }

    public function year(): static
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2025-03-01');

        return $this->state([
            'type'       => PeriodType::Year,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+11 months')->format('Y-m-d'),
        ]);
    }

    public function trimester(): static
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2026-06-01');

        return $this->state([
            'type'       => PeriodType::Trimester,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+3 months')->format('Y-m-d'),
        ]);
    }

    public function active(): static
    {
        return $this->state(['status' => PeriodStatus::Active]);
    }

    public function closed(): static
    {
        return $this->state(['status' => PeriodStatus::Closed]);
    }
}
