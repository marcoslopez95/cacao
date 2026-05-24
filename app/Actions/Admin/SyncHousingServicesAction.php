<?php

namespace App\Actions\Admin;

use App\Models\HousingProfile;

class SyncHousingServicesAction
{
    /**
     * @param  array<int, array{basic_service_id: int, is_available: bool}>  $services
     */
    public function handle(HousingProfile $profile, array $services): HousingProfile
    {
        $syncData = collect($services)
            ->keyBy('basic_service_id')
            ->map(fn ($s) => ['is_available' => $s['is_available']])
            ->all();

        $profile->services()->sync($syncData);

        return $profile->load('services');
    }
}
