<?php

namespace App\Http\Resources\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\EnrollmentDetailStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/** @mixin ClassSession */
class AttendanceSheetResource extends JsonResource
{
    /**
     * Build roster, absence totals, and session count for a section's totals panel.
     *
     * @return array{roster: list<array<string, mixed>>, absence_totals: array<int, int>, sessions_counted: int}
     */
    public static function summaryForSection(Section $section): array
    {
        $countedStatuses = [ClassSessionStatus::Held->value, ClassSessionStatus::Advanced->value];

        $enrollmentDetails = $section->enrollmentDetails()
            ->with(['enrollment.student.user'])
            ->where('status', EnrollmentDetailStatus::Confirmed)
            ->get();

        $sessionsCounted = $section->classSessions()
            ->whereIn('status', $countedStatuses)
            ->count();

        $detailIds = $enrollmentDetails->pluck('id');

        $absenceTotals = AttendanceRecord::query()
            ->join('class_sessions', 'attendance_records.class_session_id', '=', 'class_sessions.id')
            ->whereIn('attendance_records.enrollment_detail_id', $detailIds)
            ->where('attendance_records.status', AttendanceStatus::Absent->value)
            ->whereIn('class_sessions.status', $countedStatuses)
            ->groupBy('attendance_records.enrollment_detail_id')
            ->select('attendance_records.enrollment_detail_id', DB::raw('count(*) as total'))
            ->pluck('total', 'enrollment_detail_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $roster = $enrollmentDetails->map(function ($detail) {
            $student = $detail->enrollment->student;
            $user = $student->user;
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $initials = mb_strtoupper(mb_substr($firstName, 0, 1).mb_substr($lastName, 0, 1));

            return [
                'enrollment_detail_id' => $detail->id,
                'student_id' => $student->id,
                'name' => trim($firstName.' '.$lastName),
                'initials' => $initials,
                'code' => $user->document_number ?? (string) $student->id,
                'status' => null,
            ];
        })->values()->all();

        return [
            'roster' => $roster,
            'absence_totals' => $absenceTotals,
            'sessions_counted' => $sessionsCounted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ClassSession $session */
        $session = $this->resource;

        $section = $session->section;

        // Confirmed enrollment details for this section
        $enrollmentDetails = $section->enrollmentDetails()
            ->with(['enrollment.student.user'])
            ->where('status', EnrollmentDetailStatus::Confirmed)
            ->get();

        // All attendance records for this session, keyed by enrollment_detail_id
        $sessionRecords = AttendanceRecord::where('class_session_id', $session->id)
            ->get()
            ->keyBy('enrollment_detail_id');

        // Sessions counted for absence denominator: held or advanced (not recovered)
        $countedStatuses = [ClassSessionStatus::Held->value, ClassSessionStatus::Advanced->value];

        $sessionsCounted = $section->classSessions()
            ->whereIn('status', $countedStatuses)
            ->count();

        // Absence totals: count absences per enrollment_detail across counted sessions
        $detailIds = $enrollmentDetails->pluck('id');

        $absenceTotals = AttendanceRecord::query()
            ->join('class_sessions', 'attendance_records.class_session_id', '=', 'class_sessions.id')
            ->whereIn('attendance_records.enrollment_detail_id', $detailIds)
            ->where('attendance_records.status', AttendanceStatus::Absent->value)
            ->whereIn('class_sessions.status', $countedStatuses)
            ->groupBy('attendance_records.enrollment_detail_id')
            ->select('attendance_records.enrollment_detail_id', DB::raw('count(*) as total'))
            ->pluck('total', 'enrollment_detail_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $roster = $enrollmentDetails->map(function ($detail) use ($sessionRecords) {
            $student = $detail->enrollment->student;
            $user = $student->user;
            $record = $sessionRecords->get($detail->id);

            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $initials = mb_strtoupper(
                mb_substr($firstName, 0, 1).mb_substr($lastName, 0, 1)
            );

            return [
                'enrollment_detail_id' => $detail->id,
                'student_id' => $student->id,
                'name' => trim($firstName.' '.$lastName),
                'initials' => $initials,
                'code' => $user->document_number ?? (string) $student->id,
                'status' => $record?->status->value,
            ];
        })->values()->all();

        return [
            'session' => (new ClassSessionResource($session->load(['attendanceRecords', 'linkedSession', 'uploadedBy'])))->toArray($request),
            'roster' => $roster,
            'absence_totals' => $absenceTotals,
            'sessions_counted' => $sessionsCounted,
        ];
    }
}
