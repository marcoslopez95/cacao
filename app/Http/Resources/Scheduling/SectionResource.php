<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
}
