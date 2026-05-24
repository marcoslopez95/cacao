<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\UserAddressWrapper;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UpdateUserAddressAction
{
    public function handle(UserAddress $address, UserAddressWrapper $wrapper): UserAddress
    {
        return DB::transaction(function () use ($address, $wrapper): UserAddress {
            if ($wrapper->isPrimary()) {
                UserAddress::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_primary' => false]);
            }

            $address->update([
                'country_id' => $wrapper->getCountryId(),
                'state_id' => $wrapper->getStateId(),
                'municipality_id' => $wrapper->getMunicipalityId(),
                'parish_id' => $wrapper->getParishId(),
                'geographic_zone_id' => $wrapper->getGeographicZoneId(),
                'address_line1' => $wrapper->getAddressLine1(),
                'address_line2' => $wrapper->getAddressLine2(),
                'is_primary' => $wrapper->isPrimary(),
            ]);

            return $address->fresh();
        });
    }
}
