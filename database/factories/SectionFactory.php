<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Period;
use App\Models\Pensum;
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

    public function school(): static
    {
        $pensum = Pensum::factory()->create([
            'period_type'   => 'year',
            'total_periods' => 6,
        ]);
        $grade  = fake()->numberBetween(1, 6);
        $letter = fake()->randomElement(['A', 'B', 'C']);

        return $this->state([
            'type'            => SectionType::School,
            'period_id'       => Period::factory()->year(),
            'pensum_id'       => $pensum->id,
            'subject_id'      => null,
            'code'            => (string) $grade . $letter,
            'grade'           => $grade,
            'letter'          => $letter,
            'main_teacher_id' => null,
            'classroom_id'    => null,
        ]);
    }

    public function forPeriodAndSubject(Period $period, Subject $subject): static
    {
        return $this->state([
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function forPensumAndGrade(Pensum $pensum, int $grade, string $letter = 'A'): static
    {
        return $this->state([
            'type'       => SectionType::School,
            'pensum_id'  => $pensum->id,
            'subject_id' => null,
            'code'       => (string) $grade . $letter,
            'grade'      => $grade,
            'letter'     => $letter,
        ]);
    }
}
