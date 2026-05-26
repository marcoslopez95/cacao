<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\DemographicProfileWrapper;
use App\Models\DemographicProfile;

class UpsertDemographicProfileAction
{
    public function handle(int $userId, DemographicProfileWrapper $wrapper): DemographicProfile
    {
        $attributes = [
            'birth_city' => $wrapper->getBirthCity(),
            'birth_state_id' => $wrapper->getBirthStateId(),
            'birth_country_id' => $wrapper->getBirthCountryId(),
            'is_indigenous' => $wrapper->getIsIndigenous(),
            'indigenous_community' => $wrapper->getIndigenousCommunity(),
            'native_language_id' => $wrapper->getNativeLanguageId(),
            'is_returned_migrant' => $wrapper->getIsReturnedMigrant(),
            'previous_country_id' => $wrapper->getPreviousCountryId(),
            'practices_sport' => $wrapper->getPracticesSport(),
            'sport' => $wrapper->getSport(),
            'cultural_activities' => $wrapper->getCulturalActivities(),
        ];

        // Only overwrite religion_id when the key was explicitly included in the
        // validated payload. An absent key means the frontend never received it
        // (e.g. a non-privileged user), so the existing DB value must be preserved.
        if ($wrapper->hasReligionId()) {
            $attributes['religion_id'] = $wrapper->getReligionId();
        }

        return DemographicProfile::updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );
    }
}
