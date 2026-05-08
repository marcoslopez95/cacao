<?php

namespace App\Services\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Professor;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

class ScheduleConflictService
{
    /**
     * Returns the conflicting schedule if the classroom is double-booked, or null.
     */
    public function classroomConflict(Schedule $schedule): ?Schedule
    {
        $section         = $schedule->section;
        $newValidFrom    = $schedule->valid_from instanceof Carbon
            ? $schedule->valid_from->toDateString()
            : $schedule->valid_from;
        $newValidUntil   = $schedule->valid_until
            ? ($schedule->valid_until instanceof Carbon ? $schedule->valid_until->toDateString() : $schedule->valid_until)
            : $section->period->end_date->toDateString();

        return Schedule::query()
            ->where('id', '!=', $schedule->id ?? 0)
            ->where('classroom_id', $schedule->classroom_id)
            ->where('day_of_week', $schedule->day_of_week instanceof \BackedEnum ? $schedule->day_of_week->value : $schedule->day_of_week)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where('valid_from', '<=', $newValidUntil)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $newValidFrom))
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->first();
    }

    /**
     * Returns the conflicting schedule if the professor is double-booked, or null.
     */
    public function professorConflict(Schedule $schedule): ?Schedule
    {
        $section         = $schedule->section;
        $newValidFrom    = $schedule->valid_from instanceof Carbon
            ? $schedule->valid_from->toDateString()
            : $schedule->valid_from;
        $newValidUntil   = $schedule->valid_until
            ? ($schedule->valid_until instanceof Carbon ? $schedule->valid_until->toDateString() : $schedule->valid_until)
            : $section->period->end_date->toDateString();

        return Schedule::query()
            ->where('id', '!=', $schedule->id ?? 0)
            ->where('professor_id', $schedule->professor_id)
            ->where('day_of_week', $schedule->day_of_week instanceof \BackedEnum ? $schedule->day_of_week->value : $schedule->day_of_week)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where('valid_from', '<=', $newValidUntil)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $newValidFrom))
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->first();
    }

    /**
     * Returns true if adding this schedule would exceed the professor's weekly hour limit.
     */
    public function professorWeeklyHoursExceeded(Schedule $schedule): bool
    {
        $professor = $schedule->professor;
        $excludeId = $schedule->exists ? $schedule->id : null;

        $current  = $this->professorCurrentWeeklyHours($professor, $excludeId);
        $newHours = $this->slotHours($schedule->start_time, $schedule->end_time);

        return ($current + $newHours) > $professor->weekly_hour_limit;
    }

    /**
     * Returns total weekly hours assigned to a professor (optionally excluding one schedule).
     */
    public function professorCurrentWeeklyHours(Professor $professor, ?int $excludeId = null): float
    {
        $query = Schedule::query()
            ->where('professor_id', $professor->id)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get(['start_time', 'end_time'])
            ->sum(fn ($s) => $this->slotHours($s->start_time, $s->end_time));
    }

    private function slotHours(string $start, string $end): float
    {
        return Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60;
    }
}
