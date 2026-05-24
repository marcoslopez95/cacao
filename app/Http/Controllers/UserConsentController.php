<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserConsentAction;
use App\Actions\RevokeUserConsentAction;
use App\Http\Requests\StoreUserConsentRequest;
use App\Http\Resources\UserConsentResource;
use App\Http\Wrappers\StoreUserConsentWrapper;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserConsentController extends Controller
{
    public function __construct(
        private readonly CreateUserConsentAction $createAction,
        private readonly RevokeUserConsentAction $revokeAction,
    ) {}

    public function store(StoreUserConsentRequest $request, User $user): UserConsentResource
    {
        $consent = $this->createAction->handle($user, new StoreUserConsentWrapper($request->validated()), $request);

        return new UserConsentResource($consent);
    }

    public function revoke(Request $request, User $user, UserConsent $consent): UserConsentResource
    {
        Gate::authorize('revoke', $consent);

        return new UserConsentResource($this->revokeAction->handle($consent));
    }
}
