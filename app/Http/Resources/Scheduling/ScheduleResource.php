<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section' => [
                'id' => $this->section->id,
                'code' => $this->section->code,
                'type' => $this->section->type->value,
            ],
            'professor' => [
                'id' => $this->professor->id,
                'user' => ['name' => $this->professor->user->name],
            ],
            'classroom' => [
                'id' => $this->classroom->id,
                'identifier' => $this->classroom->identifier,
            ],
            'subject' => [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ],
            'career' => $this->subject->pensum?->career
                ? [
                    'id' => $this->subject->pensum->career->id,
                    'name' => $this->subject->pensum->career->name,
                ]
                : null,
            'dayOfWeek' => $this->day_of_week->value,
            'dayLabel' => $this->day_of_week->label(),
            'startTime' => substr($this->start_time, 0, 5),
            'endTime' => substr($this->end_time, 0, 5),
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'validFrom' => $this->valid_from->toDateString(),
            'validUntil' => $this->valid_until?->toDateString(),
        ];
    }
}
