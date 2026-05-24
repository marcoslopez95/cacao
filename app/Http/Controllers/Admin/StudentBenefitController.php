<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AttachStudentBenefitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentBenefitRequest;
use App\Http\Resources\Admin\StudentBenefitResource;
use App\Http\Wrappers\Admin\StudentBenefitWrapper;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StudentBenefitController extends Controller
{
    public function __construct(
        private readonly AttachStudentBenefitAction $action,
    ) {}

    public function store(StoreStudentBenefitRequest $request, Student $student, InstitutionalBenefit $benefit): StudentBenefitResource
    {
        $studentBenefit = $this->action->handle($student, $benefit, new StudentBenefitWrapper($request->validated()));

        return new StudentBenefitResource($studentBenefit->load('benefit'));
    }

    public function destroy(Request $request, Student $student, InstitutionalBenefit $benefit): Response
    {
        abort_unless($request->user()->hasAnyRole(['Administrador', 'Coordinador']), 403);

        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->delete();

        return response()->noContent();
    }
}
