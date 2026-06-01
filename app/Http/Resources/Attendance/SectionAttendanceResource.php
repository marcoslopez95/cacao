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
    /** Career colour palette — round-robin by career ID, mirrors frontend useScheduleLayout.ts */
    private const CAREER_COLORS = [
        '#C8521A', '#7C5A3A', '#2E7D5C', '#5B5A8A',
        '#A36B2D', '#3D6B8A', '#7A3578', '#2D7A6B',
    ];

    /**
     * @return array{
     *     id: int,
     *     code: string|null,
     *     subject: string|null,
     *     cohort: string,
     *     career: string|null,
     *     careerColor: string,
     *     teacherName: string,
     *     teacherInitials: string,
     *     scheduleDisplay: string,
     *     room: string,
     *     rosterCount: int,
     * }
     */
    public function toArray(Request $request): array
    {
        $teacher = $this->mainTeacher?->user;
        $teacherFirst = $teacher?->first_name ?? '';
        $teacherLast = $teacher?->last_name ?? '';
        $teacherName = trim($teacherFirst.' '.$teacherLast) ?: 'Sin asignar';
        $teacherInitials = mb_strtoupper(
            mb_substr($teacherFirst, 0, 1).mb_substr($teacherLast, 0, 1)
        ) ?: '—';

        // Career via subject → pensum → career (university) or via pensum (school)
        $career = $this->subject?->pensum?->career
            ?? $this->pensum?->career;

        $careerColor = $career
            ? self::CAREER_COLORS[($career->id - 1) % count(self::CAREER_COLORS)]
            : '#888780';

        // Cohort label: for university sections use section code; for school use grade + letter
        $cohort = $this->code ?? ($this->grade ? (string) $this->grade.($this->letter ?? '') : '—');

        // Schedule display: summarise all schedule slots
        $scheduleDisplay = $this->schedules->map(function ($s) {
            $days = ['monday' => 'Lun', 'tuesday' => 'Mar', 'wednesday' => 'Mié',
                'thursday' => 'Jue', 'friday' => 'Vie', 'saturday' => 'Sáb'];
            $day = $days[$s->day] ?? $s->day;
            $from = substr($s->start_time, 0, 5);
            $to = substr($s->end_time, 0, 5);

            return "{$day} {$from}–{$to}";
        })->implode(' · ');

        if ($scheduleDisplay === '') {
            $scheduleDisplay = 'Sin horario';
        }

        // Room: prefer theory classroom name, then lab, then generic classroom
        $room = $this->theoryClassroom?->name
            ?? $this->labClassroom?->name
            ?? $this->classroom?->name
            ?? 'Sin aula';

        $rosterCount = $this->enrollmentDetails()
            ->where('status', 'confirmed')
            ->count();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'subject' => $this->subject?->name,
            'cohort' => $cohort,
            'career' => $career?->name,
            'careerColor' => $careerColor,
            'teacherName' => $teacherName,
            'teacherInitials' => $teacherInitials,
            'scheduleDisplay' => $scheduleDisplay,
            'room' => $room,
            'rosterCount' => $rosterCount,
        ];
    }
}
