<?php

namespace App\Http\Resources\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassSession */
class ClassSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $records = $this->attendanceRecords;

        return [
            'id' => $this->id,
            'sectionId' => $this->section_id,
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'professorPresent' => $this->professor_present,
            'uploadedBy' => ! $this->professor_present && $this->uploadedBy
                ? $this->uploadedBy->name
                : null,
            'topic' => $this->topic,
            'heldAt' => $this->held_at?->format('Y-m-d'),
            'linkedSession' => $this->linkedSession ? [
                'id' => $this->linkedSession->id,
                'date' => $this->linkedSession->held_at?->format('Y-m-d'),
                'topic' => $this->linkedSession->topic,
                'status' => $this->linkedSession->status->value,
            ] : null,
            'present' => $records->filter(fn ($r) => $r->status === AttendanceStatus::Present)->count(),
            'absent' => $records->filter(fn ($r) => $r->status === AttendanceStatus::Absent)->count(),
            'hasRecord' => $records->isNotEmpty(),
        ];
    }
}
