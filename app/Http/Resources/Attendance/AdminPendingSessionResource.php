<?php

namespace App\Http\Resources\Attendance;

use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassSession */
class AdminPendingSessionResource extends JsonResource
{
    /** Career colour palette — round-robin by career ID */
    private const CAREER_COLORS = [
        '#C8521A', '#7C5A3A', '#2E7D5C', '#5B5A8A',
        '#A36B2D', '#3D6B8A', '#7A3578', '#2D7A6B',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $section = $this->section;

        $teacher = $section->mainTeacher?->user;
        $teacherFirst = $teacher?->first_name ?? '';
        $teacherLast = $teacher?->last_name ?? '';
        $teacherName = trim($teacherFirst.' '.$teacherLast) ?: 'Sin asignar';

        // Career via subject → pensum → career (university) or via pensum (school)
        $career = $section->subject?->pensum?->career
            ?? $section->pensum?->career;

        $careerColor = $career
            ? self::CAREER_COLORS[($career->id - 1) % count(self::CAREER_COLORS)]
            : '#C8521A';

        // Cohort label: for university sections use section code; for school use grade + letter
        $cohort = $section->code ?? ($section->grade ? (string) $section->grade.($section->letter ?? '') : '—');

        return [
            'id' => $this->id,
            'sectionId' => $this->section_id,
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'professorPresent' => $this->professor_present,
            'uploadedBy' => null,
            'topic' => $this->topic,
            'heldAt' => $this->held_at?->format('Y-m-d'),
            'linkedSession' => null,
            'present' => 0,
            'absent' => 0,
            'hasRecord' => false,
            'subject' => $section->subject?->name,
            'code' => $section->subject?->code ?? $section->code,
            'cohort' => $cohort,
            'career' => $career?->name,
            'careerColor' => $careerColor,
            'teacherName' => $teacherName,
        ];
    }
}
