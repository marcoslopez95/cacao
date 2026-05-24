<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\StudentBenefitWrapper;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Student;
use App\Models\StudentBenefit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttachStudentBenefitAction
{
    public function handle(Student $student, InstitutionalBenefit $benefit, StudentBenefitWrapper $wrapper): StudentBenefit
    {
        $exists = DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'benefit_id' => ['El beneficio ya está asignado al estudiante.'],
            ]);
        }

        DB::table('student_benefits')->insert([
            'student_id' => $student->id,
            'benefit_id' => $benefit->id,
            'is_active' => $wrapper->isActive(),
            'since' => $wrapper->getSince(),
            'until' => $wrapper->getUntil(),
        ]);

        return StudentBenefit::where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->firstOrFail();
    }
}
