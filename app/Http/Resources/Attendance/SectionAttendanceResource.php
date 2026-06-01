<?php

namespace App\Http\Resources\Attendance;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Section
 */
class SectionAttendanceResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     subject: string|null,
     *     teacher: string|null,
     *     schedules: array<int, array{id: int, day: string, start_time: string, end_time: string}>,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'subject' => $this->subject?->name,
            'teacher' => $this->mainTeacher?->user?->name,
            'schedules' => $this->schedules->map(fn ($s) => [
                'id' => $s->id,
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ])->all(),
        ];
    }
}
