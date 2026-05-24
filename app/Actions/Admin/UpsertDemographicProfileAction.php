<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\DemographicProfileWrapper;
use App\Models\DemographicProfile;

class UpsertDemographicProfileAction
{
    public function handle(int $userId, DemographicProfileWrapper $wrapper): DemographicProfile
    {
        return DemographicProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'birth_city' => $wrapper->getBirthCity(),
                'birth_state_id' => $wrapper->getBirthStateId(),
                'birth_country_id' => $wrapper->getBirthCountryId(),
                'is_indigenous' => $wrapper->getIsIndigenous(),
                'indigenous_community' => $wrapper->getIndigenousCommunity(),
                'native_language_id' => $wrapper->getNativeLanguageId(),
                'is_returned_migrant' => $wrapper->getIsReturnedMigrant(),
                'previous_country_id' => $wrapper->getPreviousCountryId(),
                'religion_id' => $wrapper->getReligionId(),
                'practices_sport' => $wrapper->getPracticesSport(),
                'sport' => $wrapper->getSport(),
                'cultural_activities' => $wrapper->getCulturalActivities(),
            ]
        );
    }
}
