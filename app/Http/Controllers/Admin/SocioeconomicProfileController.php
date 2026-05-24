<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpsertSocioeconomicProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSocioeconomicProfileRequest;
use App\Http\Resources\Admin\SocioeconomicProfileResource;
use App\Http\Wrappers\Admin\SocioeconomicProfileWrapper;
use App\Models\Student;

class SocioeconomicProfileController extends Controller
{
    public function __construct(
        private readonly UpsertSocioeconomicProfileAction $action,
    ) {}

    public function upsert(StoreSocioeconomicProfileRequest $request, Student $student): SocioeconomicProfileResource
    {
        $data = array_merge($request->validated(), ['recorded_by' => $request->user()->id]);
        $profile = $this->action->handle($student, new SocioeconomicProfileWrapper($data));

        return new SocioeconomicProfileResource($profile->load(['incomeRange', 'incomeSource', 'employmentType', 'recordedBy']));
    }
}
