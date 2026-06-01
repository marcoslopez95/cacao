<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Http\Wrappers\Attendance\AttendanceSheetWrapper;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;

class TakeAttendanceAction
{
    public function handle(AttendanceSheetWrapper $wrapper): void
    {
        $sessionId = $wrapper->getClassSessionId();

        foreach ($wrapper->getMarks() as $enrollmentDetailId => $status) {
            AttendanceRecord::updateOrCreate(
                [
                    'class_session_id' => $sessionId,
                    'enrollment_detail_id' => (int) $enrollmentDetailId,
                ],
                [
                    'status' => $status,
                ]
            );
        }

        ClassSession::where('id', $sessionId)->update([
            'status' => ClassSessionStatus::Held,
            'held_at' => today()->format('Y-m-d'),
            'professor_present' => $wrapper->isProfessorPresent(),
        ]);
    }
}
