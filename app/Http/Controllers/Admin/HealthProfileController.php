<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertHealthProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHealthProfileRequest;
use App\Http\Resources\Admin\HealthProfileResource;
use App\Http\Wrappers\Admin\HealthProfileWrapper;
use App\Models\User;

class HealthProfileController extends Controller
{
    public function __construct(
        private readonly UpsertHealthProfileAction $action,
    ) {}

    public function upsert(StoreHealthProfileRequest $request, User $user): HealthProfileResource
    {
        $profile = $this->action->handle($user, new HealthProfileWrapper($request->validated()));

        return new HealthProfileResource($profile->load(['bloodType', 'disabilityType', 'insuranceType']));
    }
}
