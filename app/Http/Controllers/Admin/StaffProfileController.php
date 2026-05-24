<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertStaffProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffProfileRequest;
use App\Http\Resources\Admin\StaffProfileResource;
use App\Http\Wrappers\Admin\StaffProfileWrapper;
use App\Models\Professor;

class StaffProfileController extends Controller
{
    public function __construct(
        private readonly UpsertStaffProfileAction $action,
    ) {}

    public function upsert(StoreStaffProfileRequest $request, Professor $professor): StaffProfileResource
    {
        $profile = $this->action->handle($professor->id, new StaffProfileWrapper($request->validated()));

        return new StaffProfileResource($profile->load(['contractType', 'dedicationType', 'employmentStatus', 'coordinatedDepartment']));
    }
}
