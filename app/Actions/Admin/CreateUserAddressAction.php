<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\UserAddressWrapper;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class CreateUserAddressAction
{
    public function handle(int $userId, UserAddressWrapper $wrapper): UserAddress
    {
        return DB::transaction(function () use ($userId, $wrapper): UserAddress {
            if ($wrapper->isPrimary()) {
                UserAddress::where('user_id', $userId)->update(['is_primary' => false]);
            }

            return UserAddress::create([
                'user_id' => $userId,
                'country_id' => $wrapper->getCountryId(),
                'state_id' => $wrapper->getStateId(),
                'municipality_id' => $wrapper->getMunicipalityId(),
                'parish_id' => $wrapper->getParishId(),
                'geographic_zone_id' => $wrapper->getGeographicZoneId(),
                'address_line1' => $wrapper->getAddressLine1(),
                'address_line2' => $wrapper->getAddressLine2(),
                'is_primary' => $wrapper->isPrimary(),
            ]);
        });
    }
}
