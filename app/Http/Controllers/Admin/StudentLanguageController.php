<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AttachStudentLanguageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentLanguageRequest;
use App\Http\Resources\Admin\StudentLanguageResource;
use App\Http\Wrappers\Admin\StudentLanguageWrapper;
use App\Models\Catalogs\Language;
use App\Models\Student;
use App\Models\StudentLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentLanguageController extends Controller
{
    public function __construct(
        private readonly AttachStudentLanguageAction $action,
    ) {}

    public function store(StoreStudentLanguageRequest $request, Student $student): StudentLanguageResource
    {
        $language = $this->action->handle($student->id, new StudentLanguageWrapper($request->validated()));

        return new StudentLanguageResource($language->load(['language', 'languageLevel']));
    }

    public function destroy(Request $request, Student $student, Language $language): Response
    {
        Gate::authorize('delete', StudentLanguage::class);

        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $language->id)
            ->delete();

        return response()->noContent();
    }
}
