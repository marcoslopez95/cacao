<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
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

        $this->maybeCopyRecordsToLinkedSession($sessionId);
    }

    private function maybeCopyRecordsToLinkedSession(int $sessionId): void
    {
        $session = ClassSession::find($sessionId);

        if ($session === null || $session->type !== ClassSessionType::Advance || $session->linked_session_id === null) {
            return;
        }

        $records = AttendanceRecord::where('class_session_id', $sessionId)->get();

        foreach ($records as $record) {
            AttendanceRecord::updateOrCreate(
                [
                    'class_session_id' => $session->linked_session_id,
                    'enrollment_detail_id' => $record->enrollment_detail_id,
                ],
                [
                    'status' => $record->status,
                ]
            );
        }
    }
}
