<?php

namespace App\Http\Resources\Enrollment;

use App\Enums\ScheduleSessionType;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentCatalogSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Subject $subject */
        $subject = $this->resource['subject'];

        return [
            'id' => $subject->id,
            'code' => $subject->code,
            'name' => $subject->name,
            'credits' => $subject->credits_uc,
            'type' => 'oblig',
            'recommended_trim' => $this->resource['recommended_trim'],
            'prereqs_ok' => $this->resource['prereqs_ok'],
            'completed' => $this->resource['completed'],
            'description' => $subject->description ?? '',
            'selected_section_id' => $this->resource['selected_section_id'],
            'selected_detail_id' => $this->resource['selected_detail_id'],
            'sections' => $this->resource['sections']->map(
                fn (Section $s) => $this->sectionShape($s, $this->resource['selected_section_id'])
            )->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionShape(Section $section, ?int $selectedSectionId): array
    {
        $slots = $section->schedules->map(fn ($s) => [
            'day' => $s->day_of_week->order() - 1,
            'start' => substr($s->start_time, 0, 5),
            'end' => substr($s->end_time, 0, 5),
        ])->values()->all();

        $sessionTypes = $section->schedules->pluck('type')->map(fn ($t) => $t->value)->unique()->values();

        $hasTheory = $sessionTypes->contains(ScheduleSessionType::Theory->value);
        $hasLab = $sessionTypes->contains(ScheduleSessionType::Lab->value);
        $modality = match (true) {
            $hasTheory && $hasLab => 'Mixta',
            $hasLab => 'Laboratorio',
            $hasTheory => 'Teórica',
            default => 'Por definir',
        };

        $professor = $this->resolveProfesor($section);

        $room = $section->theoryClassroom?->identifier
            ?? $section->labClassroom?->identifier
            ?? 'Por asignar';

        return [
            'id' => $section->id,
            'code' => $section->code,
            'capacity' => $section->capacity,
            'enrolled' => $section->enrolled,
            'professor' => $professor,
            'room' => $room,
            'modality' => $modality,
            'slots' => $slots,
            'noSchedule' => empty($slots),
            'isSelected' => $section->id === $selectedSectionId,
        ];
    }

    /**
     * @return array{id: int, name: string, initials: string}
     */
    private function resolveProfesor(Section $section): array
    {
        $professor = $section->schedules->first()?->professor
            ?? $section->mainTeacher;

        if (! $professor) {
            return ['id' => 0, 'name' => 'Por asignar', 'initials' => '··'];
        }

        $name = $professor->user?->name ?? 'Por asignar';
        $words = array_filter(explode(' ', $name));
        $initials = strtoupper(implode('', array_map(fn ($w) => $w[0], array_slice($words, 0, 2))));

        return [
            'id' => $professor->id,
            'name' => $name,
            'initials' => $initials ?: '··',
        ];
    }
}
