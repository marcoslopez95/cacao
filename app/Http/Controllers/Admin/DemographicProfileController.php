<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertDemographicProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDemographicProfileRequest;
use App\Http\Resources\Admin\DemographicProfileResource;
use App\Http\Wrappers\Admin\DemographicProfileWrapper;
use App\Models\User;

class DemographicProfileController extends Controller
{
    public function __construct(
        private readonly UpsertDemographicProfileAction $action,
    ) {}

    public function upsert(StoreDemographicProfileRequest $request, User $user): DemographicProfileResource
    {
        $dp = $this->action->handle($user->id, new DemographicProfileWrapper($request->validated()));

        return new DemographicProfileResource($dp->load(['birthState', 'birthCountry', 'nativeLanguage', 'previousCountry', 'religion']));
    }
}
