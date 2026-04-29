<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class SchoolSectionWrapper extends Collection
{
    public function getPeriodId(): int
    {
        return (int) $this->get('period_id');
    }

    public function getPensumId(): int
    {
        return (int) $this->get('pensum_id');
    }

    public function getGrade(): int
    {
        return (int) $this->get('grade');
    }

    public function getLetter(): string
    {
        return strtoupper((string) $this->get('letter'));
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }

    public function getMainTeacherId(): ?int
    {
        return $this->get('main_teacher_id') ? (int) $this->get('main_teacher_id') : null;
    }

    public function getClassroomId(): ?int
    {
        return $this->get('classroom_id') ? (int) $this->get('classroom_id') : null;
    }
}
