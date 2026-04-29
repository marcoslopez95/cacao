<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'                => SectionType::University,
            'period_id'           => Period::factory()->semester(),
            'subject_id'          => Subject::factory(),
            'code'                => fake()->numerify('##'),
            'theory_classroom_id' => null,
            'lab_classroom_id'    => null,
            'capacity'            => fake()->numberBetween(20, 50),
        ];
    }

    public function university(): static
    {
        return $this->state(['type' => SectionType::University]);
    }

    public function forPeriodAndSubject(Period $period, Subject $subject): static
    {
        return $this->state([
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
        ]);
    }
}
