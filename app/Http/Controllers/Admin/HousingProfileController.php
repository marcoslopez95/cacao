<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SyncHousingServicesAction;
use App\Actions\Admin\UpsertHousingProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHousingProfileRequest;
use App\Http\Resources\Admin\HousingProfileResource;
use App\Http\Wrappers\Admin\HousingProfileWrapper;
use App\Models\Student;

class HousingProfileController extends Controller
{
    public function __construct(
        private readonly UpsertHousingProfileAction $upsertAction,
        private readonly SyncHousingServicesAction $syncAction,
    ) {}

    public function upsert(StoreHousingProfileRequest $request, Student $student): HousingProfileResource
    {
        $wrapper = new HousingProfileWrapper($request->validated());
        $profile = $this->upsertAction->handle($student, $wrapper);

        if ($wrapper->hasServices()) {
            $this->syncAction->handle($profile, $wrapper->getServices());
        }

        return new HousingProfileResource($profile->load(['housingType', 'tenureType', 'constructionMaterial', 'commuteTime', 'transportType', 'services']));
    }

    public function syncServices(StoreHousingProfileRequest $request, Student $student): HousingProfileResource
    {
        $wrapper = new HousingProfileWrapper($request->validated());
        $profile = $student->housingProfile()->firstOrCreate(['student_id' => $student->id]);
        $this->syncAction->handle($profile, $wrapper->getServices());

        return new HousingProfileResource($profile->load('services'));
    }
}
