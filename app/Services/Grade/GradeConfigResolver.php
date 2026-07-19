<?php

namespace App\Services\Grade;

use App\Enums\EducationalLevel;
use App\Enums\GradeLevel;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;

class GradeConfigResolver
{
    /**
     * Memoized resolutions for this instance, keyed by "{level}:{periodId}".
     *
     * @var array<string, GradeConfig|null>
     */
    private array $cache = [];

    /**
     * Resolve the applicable grade configuration for an enrollment detail.
     *
     * Prefers a period-specific configuration for the student's educational
     * level; falls back to the level-wide configuration without a period.
     * Memoized per instance so repeated calls for the same level/period
     * combination (e.g. every subject of the same enrollment) do not issue
     * redundant queries.
     */
    public function resolveForDetail(EnrollmentDetail $detail): ?GradeConfig
    {
        $educationalLevel = $detail->enrollment->student->educational_level ?? EducationalLevel::University;
        $level = match ($educationalLevel) {
            EducationalLevel::Primary, EducationalLevel::Secondary => GradeLevel::PrimarySecondary,
            EducationalLevel::University => GradeLevel::University,
        };
        $periodId = $detail->enrollment->period_id;

        $cacheKey = "{$level->value}:{$periodId}";

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        return $this->cache[$cacheKey] = $this->resolve($level, $periodId);
    }

    private function resolve(GradeLevel $level, ?int $periodId): ?GradeConfig
    {
        if ($periodId) {
            $config = GradeConfig::with('slots')
                ->where('level', $level)
                ->where('period_id', $periodId)
                ->first();

            if ($config) {
                return $config;
            }
        }

        return GradeConfig::with('slots')
            ->where('level', $level)
            ->whereNull('period_id')
            ->first();
    }
}
