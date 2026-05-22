<?php

namespace App\Http\Wrappers\Enrollment;

use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;

class EnrollmentDetailWrapper extends Collection
{
    public function getSubjectId(): int
    {
        return (int) $this->get('subject_id');
    }

    public function getSectionId(): int
    {
        return (int) $this->get('section_id');
    }

    public function getSubject(): Subject
    {
        return Subject::findOrFail($this->getSubjectId());
    }

    public function getSection(): Section
    {
        return Section::findOrFail($this->getSectionId());
    }
}
