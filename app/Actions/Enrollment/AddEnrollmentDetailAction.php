<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentDetailStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Enrollment\EnrollmentCacheManager;

class AddEnrollmentDetailAction
{
    public function __construct(private readonly EnrollmentCacheManager $cache) {}

    public function handle(Enrollment $enrollment, Subject $subject, Section $section): EnrollmentDetail
    {
        $detail = EnrollmentDetail::create([
            'enrollment_id' => $enrollment->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'status' => EnrollmentDetailStatus::Draft,
        ]);

        $this->cache->decrementQuota($section);

        return $detail;
    }
}
