<?php

namespace App\Http\Resources\Scheduling;

use App\Enums\SectionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->type === SectionType::School) {
            return $this->schoolShape();
        }

        return $this->universityShape();
    }

    /**
     * @return array<string, mixed>
     */
    private function universityShape(): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type->value,
            'code'            => $this->code,
            'capacity'        => $this->capacity,
            'period'          => [
                'id'   => $this->period->id,
                'name' => $this->period->name,
                'type' => $this->period->type->value,
            ],
            'subject'         => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ],
            'theoryClassroom' => $this->theory_classroom_id ? [
                'id'         => $this->theoryClassroom->id,
                'identifier' => $this->theoryClassroom->identifier,
                'capacity'   => $this->theoryClassroom->capacity,
            ] : null,
            'labClassroom'    => $this->lab_classroom_id ? [
                'id'         => $this->labClassroom->id,
                'identifier' => $this->labClassroom->identifier,
                'capacity'   => $this->labClassroom->capacity,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolShape(): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type->value,
            'grade'       => $this->grade,
            'letter'      => $this->letter,
            'code'        => $this->code,
            'capacity'    => $this->capacity,
            'period'      => [
                'id'   => $this->period->id,
                'name' => $this->period->name,
                'type' => $this->period->type->value,
            ],
            'pensum'      => [
                'id'     => $this->pensum->id,
                'name'   => $this->pensum->name,
                'career' => [
                    'id'   => $this->pensum->career->id,
                    'name' => $this->pensum->career->name,
                ],
            ],
            'mainTeacher' => $this->main_teacher_id ? [
                'id'   => $this->mainTeacher->id,
                'user' => [
                    'id'   => $this->mainTeacher->user->id,
                    'name' => $this->mainTeacher->user->name,
                ],
            ] : null,
            'classroom'   => $this->classroom_id ? [
                'id'         => $this->classroom->id,
                'identifier' => $this->classroom->identifier,
                'capacity'   => $this->classroom->capacity,
            ] : null,
            'subjects'    => $this->sectionSubjects->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
                'code' => $s->code,
            ])->values()->toArray(),
        ];
    }
}
