<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertStudentBackgroundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentBackgroundRequest;
use App\Http\Resources\Admin\StudentBackgroundResource;
use App\Http\Wrappers\Admin\StudentBackgroundWrapper;
use App\Models\Student;

class StudentBackgroundController extends Controller
{
    public function __construct(
        private readonly UpsertStudentBackgroundAction $action,
    ) {}

    public function upsert(StoreStudentBackgroundRequest $request, Student $student): StudentBackgroundResource
    {
        $bg = $this->action->handle($student->id, new StudentBackgroundWrapper($request->validated()));

        return new StudentBackgroundResource($bg->load(['institutionType', 'transferReason', 'digitalLevel', 'motherEducationLevel', 'fatherEducationLevel']));
    }
}
