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
            'section_id' => $this->section_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'professor_present' => $this->professor_present,
            'uploaded_by' => ! $this->professor_present && $this->uploadedBy
                ? $this->uploadedBy->name
                : null,
            'topic' => $this->topic,
            'held_at' => $this->held_at?->format('Y-m-d'),
            'linked_session' => $this->linkedSession ? [
                'id' => $this->linkedSession->id,
                'date' => $this->linkedSession->held_at?->format('Y-m-d'),
                'topic' => $this->linkedSession->topic,
                'status' => $this->linkedSession->status->value,
            ] : null,
            'present' => $records->filter(fn ($r) => $r->status === AttendanceStatus::Present)->count(),
            'absent' => $records->filter(fn ($r) => $r->status === AttendanceStatus::Absent)->count(),
            'has_record' => $records->isNotEmpty(),
        ];
    }
}
