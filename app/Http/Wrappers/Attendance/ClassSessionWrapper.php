<?php

namespace App\Http\Wrappers\Attendance;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use Illuminate\Support\Collection;

class ClassSessionWrapper extends Collection
{
    public function getSectionId(): int
    {
        return (int) $this->get('section_id');
    }

    public function getType(): ClassSessionType
    {
        $value = $this->get('type');

        return $value instanceof ClassSessionType
            ? $value
            : ClassSessionType::from($value);
    }

    public function getStatus(): ClassSessionStatus
    {
        $value = $this->get('status', ClassSessionStatus::Scheduled->value);

        return $value instanceof ClassSessionStatus
            ? $value
            : ClassSessionStatus::from($value);
    }

    public function getLinkedSessionId(): ?int
    {
        $v = $this->get('linked_session_id');

        return $v !== null ? (int) $v : null;
    }

    public function getTopic(): ?string
    {
        return $this->get('topic');
    }

    public function getHeldAt(): ?string
    {
        return $this->get('held_at');
    }

    public function isProfessorPresent(): bool
    {
        return (bool) $this->get('professor_present', true);
    }
}
