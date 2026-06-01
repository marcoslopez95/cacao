<?php

namespace App\Http\Wrappers\Attendance;

use Illuminate\Support\Collection;

class AttendanceSheetWrapper extends Collection
{
    public function getClassSessionId(): int
    {
        return (int) $this->get('class_session_id');
    }

    /**
     * @return array<int, string> enrollment_detail_id => 'present'|'absent'
     */
    public function getMarks(): array
    {
        return (array) $this->get('marks', []);
    }

    public function isProfessorPresent(): bool
    {
        return (bool) $this->get('professor_present', true);
    }
}
