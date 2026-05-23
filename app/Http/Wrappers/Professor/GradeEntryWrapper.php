<?php

namespace App\Http\Wrappers\Professor;

use Illuminate\Support\Collection;

class GradeEntryWrapper extends Collection
{
    public function getEnrollmentDetailId(): int
    {
        return (int) $this->get('enrollment_detail_id');
    }

    public function getGradeSlotId(): int
    {
        return (int) $this->get('grade_slot_id');
    }

    public function getLapseId(): ?int
    {
        $v = $this->get('lapse_id');

        return $v !== null ? (int) $v : null;
    }

    public function getParentId(): ?int
    {
        $v = $this->get('parent_id');

        return $v !== null ? (int) $v : null;
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getWeight(): ?string
    {
        $v = $this->get('weight');

        return $v !== null ? (string) $v : null;
    }

    public function getValue(): ?string
    {
        $v = $this->get('value');

        return $v !== null ? (string) $v : null;
    }

    public function isSubEntry(): bool
    {
        return $this->getParentId() !== null;
    }
}
