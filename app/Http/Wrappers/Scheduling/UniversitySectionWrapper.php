<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class UniversitySectionWrapper extends Collection
{
    public function getPeriodId(): int
    {
        return (int) $this->get('period_id');
    }

    public function getSubjectId(): int
    {
        return (int) $this->get('subject_id');
    }

    public function getCode(): string
    {
        return (string) $this->get('code');
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }

    public function getTheoryClassroomId(): ?int
    {
        return $this->get('theory_classroom_id') ? (int) $this->get('theory_classroom_id') : null;
    }

    public function getLabClassroomId(): ?int
    {
        return $this->get('lab_classroom_id') ? (int) $this->get('lab_classroom_id') : null;
    }
}
