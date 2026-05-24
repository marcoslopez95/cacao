<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateUserAddressAction;
use App\Actions\Admin\UpdateUserAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserAddressRequest;
use App\Http\Resources\Admin\UserAddressResource;
use App\Http\Wrappers\Admin\UserAddressWrapper;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserAddressController extends Controller
{
    public function __construct(
        private readonly CreateUserAddressAction $creator,
        private readonly UpdateUserAddressAction $updater,
    ) {}

    public function index(User $user): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', UserAddress::class);

        return UserAddressResource::collection(
            $user->addresses()->with(['country', 'state', 'municipality', 'parish', 'geographicZone'])->get()
        );
    }

    public function store(StoreUserAddressRequest $request, User $user): UserAddressResource
    {
        $address = $this->creator->handle($user->id, new UserAddressWrapper($request->validated()));

        return new UserAddressResource($address->load(['country', 'state', 'municipality', 'parish', 'geographicZone']));
    }

    public function update(StoreUserAddressRequest $request, User $user, UserAddress $address): UserAddressResource
    {
        Gate::authorize('update', $address);

        $address = $this->updater->handle($address, new UserAddressWrapper($request->validated()));

        return new UserAddressResource($address->load(['country', 'state', 'municipality', 'parish', 'geographicZone']));
    }

    public function destroy(User $user, UserAddress $address): Response
    {
        Gate::authorize('delete', $address);

        $address->delete();

        return response()->noContent();
    }
}
