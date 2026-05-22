<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Enrollment\EnrollmentCacheManager;
use App\Services\Enrollment\PrerequisiteValidator;
use Illuminate\Support\Facades\DB;

class ConfirmEnrollmentAction
{
    public function __construct(
        private readonly PrerequisiteValidator $validator,
        private readonly EnrollmentCacheManager $cache,
    ) {}

    public function handle(Enrollment $enrollment): Enrollment
    {
        $details = $enrollment->draftDetails()->with(['subject', 'section'])->get();

        if ($details->isEmpty()) {
            throw new \InvalidArgumentException('La inscripción no tiene materias pendientes de confirmar.');
        }

        return DB::transaction(function () use ($enrollment, $details) {
            $sectionIds = $details->pluck('section_id')->unique()->values()->toArray();
            $sections = Section::whereIn('id', $sectionIds)->lockForUpdate()->get();

            foreach ($details as $detail) {
                $section = $sections->find($detail->section_id);

                $confirmedCount = EnrollmentDetail::where('section_id', $section->id)
                    ->where('status', 'confirmed')
                    ->count();

                if ($confirmedCount >= $section->capacity) {
                    throw new \RuntimeException("No hay cupos disponibles en la sección {$section->code}.");
                }

                if (! $this->validator->canTake($enrollment->student, $detail->subject)) {
                    $missing = $this->validator->getMissingPrerequisites($enrollment->student, $detail->subject);
                    $codes = Subject::whereIn('id', $missing)->pluck('code')->join(', ');
                    throw new \RuntimeException("Prerrequisitos no cumplidos: {$codes}.");
                }
            }

            $enrollment->details()->where('status', 'draft')->update(['status' => 'confirmed']);

            $totalCredits = (int) $enrollment->confirmedDetails()
                ->join('subjects', 'enrollment_details.subject_id', '=', 'subjects.id')
                ->sum('subjects.credits_uc');

            $enrollment->update([
                'status' => EnrollmentStatus::Confirmed,
                'uc_inscritas' => $totalCredits,
            ]);

            foreach ($sections as $section) {
                $this->cache->invalidateQuota($section);
            }

            return $enrollment->fresh();
        });
    }
}
