<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertFamilyProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFamilyProfileRequest;
use App\Http\Resources\Admin\FamilyProfileResource;
use App\Http\Wrappers\Admin\FamilyProfileWrapper;
use App\Models\Student;

class FamilyProfileController extends Controller
{
    public function __construct(
        private readonly UpsertFamilyProfileAction $action,
    ) {}

    public function upsert(StoreFamilyProfileRequest $request, Student $student): FamilyProfileResource
    {
        $fp = $this->action->handle($student->id, new FamilyProfileWrapper($request->validated()));

        return new FamilyProfileResource($fp->load(['guardianMaritalStatus', 'livingArrangement', 'householdHeadType']));
    }
}
