<?php

namespace App\Http\Resources\Academic;

use App\Enums\PeriodStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentShowResource extends JsonResource
{
    /**
     * Transform the student model into a full academic profile array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            // Identity (from user relation)
            'name' => $this->user->name,
            'email' => $this->user->email,
            'cedula' => $this->user->document ?? null,
            'student_code' => $this->student_code,

            // Education level
            'educational_level' => $this->educational_level?->value,

            // Career / pensum
            'career_name' => $this->pensum?->career?->name,
            'pensum_name' => $this->pensum?->name,
            'pensum_total_credits' => null,

            // Academic data
            'academic_year' => $this->academic_year,
            'academic_status' => $this->academicStatus?->name,
            'modality' => $this->modality?->name,
            'shift' => $this->shift?->name,
            'cumulative_gpa' => $this->cumulative_gpa !== null ? (string) $this->cumulative_gpa : null,
            'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),

            // Active enrollment (period with status = 'active')
            'active_enrollment' => $this->resolveActiveEnrollment(),

            // Guardians (only relevant for primary / secondary levels)
            'guardians' => $this->guardians->map(fn ($guardian) => [
                'id' => $guardian->id,
                'name' => $guardian->user->name,
                'email' => $guardian->user->email,
                'kinship' => $guardian->pivot->kinship_type_id,
                'primary' => (bool) $guardian->pivot->is_primary,
            ])->values(),

            // Full enrollment history
            'enrollments' => $this->enrollments->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'period_name' => $enrollment->period?->name,
                'status' => $enrollment->status?->value,
                'uc_inscritas' => $enrollment->uc_inscritas,
                'uc_disponibles' => $enrollment->uc_disponibles,
            ])->values(),
        ];
    }

    /**
     * Find the enrollment belonging to the currently active period.
     *
     * @return array<string, mixed>|null
     */
    private function resolveActiveEnrollment(): ?array
    {
        $active = $this->enrollments->first(
            fn ($enrollment) => $enrollment->period?->status === PeriodStatus::Active
        );

        if ($active === null) {
            return null;
        }

        return [
            'id' => $active->id,
            'period_name' => $active->period->name,
            'status' => $active->status?->value,
            'uc_inscritas' => $active->uc_inscritas,
            'uc_disponibles' => $active->uc_disponibles,
        ];
    }
}
