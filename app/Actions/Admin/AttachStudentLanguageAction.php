<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\StudentLanguageWrapper;
use App\Models\StudentLanguage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttachStudentLanguageAction
{
    public function handle(int $studentId, StudentLanguageWrapper $wrapper): StudentLanguage
    {
        $exists = DB::table('student_languages')
            ->where('student_id', $studentId)
            ->where('language_id', $wrapper->getLanguageId())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'language_id' => ['Este idioma ya está registrado para el estudiante.'],
            ]);
        }

        DB::transaction(function () use ($studentId, $wrapper) {
            if ($wrapper->isMotherTongue()) {
                DB::table('student_languages')
                    ->where('student_id', $studentId)
                    ->update(['is_mother_tongue' => false]);
            }

            DB::table('student_languages')->insert([
                'student_id' => $studentId,
                'language_id' => $wrapper->getLanguageId(),
                'language_level_id' => $wrapper->getLanguageLevelId(),
                'is_mother_tongue' => $wrapper->isMotherTongue(),
            ]);
        });

        return StudentLanguage::where('student_id', $studentId)
            ->where('language_id', $wrapper->getLanguageId())
            ->firstOrFail();
    }
}
