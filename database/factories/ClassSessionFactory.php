<?php

namespace Database\Factories;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Models\ClassSession;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'uploaded_by_id' => null,
            'linked_session_id' => null,
            'type' => ClassSessionType::Regular,
            'status' => ClassSessionStatus::Scheduled,
            'professor_present' => true,
            'topic' => fake()->sentence(),
            'held_at' => null,
        ];
    }

    public function forSection(Section $section): static
    {
        return $this->state(['section_id' => $section->id]);
    }
}
