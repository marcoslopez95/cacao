<?php

namespace App\Services\Enrollment;

use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EnrollmentCacheManager
{
    private const CACHE_TTL = 30; // seconds

    public function getAvailableQuota(Section $section): int
    {
        $key = "enrollments:section:{$section->id}:available";

        return Cache::remember($key, self::CACHE_TTL, function () use ($section) {
            return $this->calculateAvailableQuota($section);
        });
    }

    private function calculateAvailableQuota(Section $section): int
    {
        $enrolled = DB::table('enrollment_details')
            ->where('section_id', $section->id)
            ->whereIn('status', ['draft', 'confirmed'])
            ->count();

        return max(0, $section->capacity - $enrolled);
    }

    public function decrementQuota(Section $section): void
    {
        $key = "enrollments:section:{$section->id}:available";
        Cache::decrement($key, 1);
    }

    public function incrementQuota(Section $section): void
    {
        $key = "enrollments:section:{$section->id}:available";
        Cache::increment($key, 1);
    }

    public function invalidateQuota(Section $section): void
    {
        Cache::forget("enrollments:section:{$section->id}:available");
    }

    public function getPrerequisites(Subject $subject): array
    {
        $key = "prerequisites:subject:{$subject->id}";

        return Cache::remember($key, 3600, function () use ($subject) {
            return $subject->prerequisites()->pluck('id')->toArray();
        });
    }

    public function getActivePensum(Student $student): ?array
    {
        $key = "pensum:student:{$student->id}:active";

        return Cache::remember($key, 1800, function () use ($student) {
            $pensum = $student->pensum;

            if (! $pensum) {
                return null;
            }

            return [
                'id' => $pensum->id,
                'uc' => $pensum->subjects()->sum('credits_uc'),
                'subjects' => $pensum->subjects()->pluck('id')->toArray(),
            ];
        });
    }

    public function getGuardian(Student $student): ?array
    {
        $key = "guardian:student:{$student->id}";

        return Cache::remember($key, 1800, function () use ($student) {
            $guardian = $student->primaryGuardian();

            if (! $guardian) {
                return null;
            }

            return [
                'id' => $guardian->id,
                'user_id' => $guardian->user_id,
            ];
        });
    }

    public function invalidateStudentCaches(Student $student): void
    {
        Cache::forget("pensum:student:{$student->id}:active");
        Cache::forget("guardian:student:{$student->id}");
    }
}
