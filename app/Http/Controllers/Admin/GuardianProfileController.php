<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertGuardianProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuardianProfileRequest;
use App\Http\Resources\Admin\GuardianProfileResource;
use App\Http\Wrappers\Admin\GuardianProfileWrapper;
use App\Models\Guardian;

class GuardianProfileController extends Controller
{
    public function __construct(
        private readonly UpsertGuardianProfileAction $action,
    ) {}

    public function upsert(StoreGuardianProfileRequest $request, Guardian $guardian): GuardianProfileResource
    {
        $profile = $this->action->handle($guardian->id, new GuardianProfileWrapper($request->validated()));

        return new GuardianProfileResource($profile->load(['educationLevel', 'maritalStatus']));
    }
}
