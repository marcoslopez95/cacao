<?php

namespace App\Http\Wrappers\Scheduling;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleWrapper extends Collection
{
    public function getSectionId(): int
    {
        return (int) $this->get('section_id');
    }

    public function getProfessorId(): int
    {
        return (int) $this->get('professor_id');
    }

    public function getClassroomId(): int
    {
        return (int) $this->get('classroom_id');
    }

    public function getSubjectId(): int
    {
        return (int) $this->get('subject_id');
    }

    public function getDayOfWeek(): DayOfWeek
    {
        return DayOfWeek::from($this->get('day_of_week'));
    }

    public function getStartTime(): string
    {
        return (string) $this->get('start_time');
    }

    public function getEndTime(): string
    {
        return (string) $this->get('end_time');
    }

    public function getType(): ScheduleSessionType
    {
        return ScheduleSessionType::from($this->get('type'));
    }

    public function getValidFrom(): Carbon
    {
        return Carbon::parse($this->get('valid_from'));
    }

    public function getValidUntil(): ?Carbon
    {
        $v = $this->get('valid_until');
        return $v ? Carbon::parse($v) : null;
    }
}
