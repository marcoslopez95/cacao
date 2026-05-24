<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\StudentBackgroundWrapper;
use App\Models\StudentBackground;

class UpsertStudentBackgroundAction
{
    public function handle(int $studentId, StudentBackgroundWrapper $wrapper): StudentBackground
    {
        return StudentBackground::updateOrCreate(
            ['student_id' => $studentId],
            [
                'previous_institution' => $wrapper->getPreviousInstitution(),
                'institution_type_id' => $wrapper->getInstitutionTypeId(),
                'graduation_year' => $wrapper->getGraduationYear(),
                'previous_gpa' => $wrapper->getPreviousGpa(),
                'repeated_grade' => $wrapper->getRepeatedGrade(),
                'repeated_grade_description' => $wrapper->getRepeatedGradeDescription(),
                'transfer_reason_id' => $wrapper->getTransferReasonId(),
                'has_prior_studies' => $wrapper->getHasPriorStudies(),
                'prior_studies_description' => $wrapper->getPriorStudiesDescription(),
                'digital_level_id' => $wrapper->getDigitalLevelId(),
                'mother_education_level_id' => $wrapper->getMotherEducationLevelId(),
                'father_education_level_id' => $wrapper->getFatherEducationLevelId(),
            ]
        );
    }
}
