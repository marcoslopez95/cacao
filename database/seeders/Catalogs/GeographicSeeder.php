<?php

namespace Database\Seeders\Catalogs;

use App\Models\Country;
use App\Models\Municipality;
use App\Models\Parish;
use App\Models\State;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $venezuela = Country::where('iso2', 'VE')->firstOrFail();

        $this->seedStates($venezuela);
        $this->seedMunicipalities();
        $this->seedParishes();
    }

    private function seedCountries(): void
    {
        /** @var array<int, array{iso2: string, iso3: string, name: string}> $countries */
        $countries = json_decode(
            (string) file_get_contents(database_path('seeders/data/countries.json')),
            true,
        );

        if (! is_array($countries)) {
            throw new \RuntimeException('Could not load seeders/data/countries.json');
        }

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['iso2' => $country['iso2']],
                [
                    'iso3' => $country['iso3'],
                    'name' => $country['name'],
                    'active' => true,
                ],
            );
        }
    }

    private function seedStates(Country $country): void
    {
        /** @var array<int, array{code: string, name: string}> $states */
        $states = json_decode(
            (string) file_get_contents(database_path('seeders/data/venezuela/states.json')),
            true,
        );

        if (! is_array($states)) {
            throw new \RuntimeException('Could not load seeders/data/venezuela/states.json');
        }

        foreach ($states as $state) {
            State::firstOrCreate(
                ['country_id' => $country->id, 'code' => $state['code']],
                ['name' => $state['name'], 'active' => true],
            );
        }
    }

    private function seedMunicipalities(): void
    {
        /** @var array<string, array<int, array{code: string, name: string}>> $municipalitiesByState */
        $municipalitiesByState = json_decode(
            (string) file_get_contents(database_path('seeders/data/venezuela/municipalities.json')),
            true,
        );

        if (! is_array($municipalitiesByState)) {
            throw new \RuntimeException('Could not load seeders/data/venezuela/municipalities.json');
        }

        $states = State::query()->pluck('id', 'code');

        foreach ($municipalitiesByState as $stateCode => $municipalities) {
            $stateId = $states[$stateCode] ?? null;

            if ($stateId === null) {
                continue;
            }

            foreach ($municipalities as $municipality) {
                Municipality::firstOrCreate(
                    ['state_id' => $stateId, 'code' => $municipality['code']],
                    ['name' => $municipality['name'], 'active' => true],
                );
            }
        }
    }

    private function seedParishes(): void
    {
        /** @var array<string, array<int, array{code: string, name: string}>> $parishesByMunicipality */
        $parishesByMunicipality = json_decode(
            (string) file_get_contents(database_path('seeders/data/venezuela/parishes.json')),
            true,
        );

        if (! is_array($parishesByMunicipality)) {
            throw new \RuntimeException('Could not load seeders/data/venezuela/parishes.json');
        }

        $municipalities = Municipality::query()->pluck('id', 'code');

        foreach ($parishesByMunicipality as $municipalityCode => $parishes) {
            $municipalityId = $municipalities[$municipalityCode] ?? null;

            if ($municipalityId === null) {
                continue;
            }

            foreach ($parishes as $parish) {
                Parish::firstOrCreate(
                    ['municipality_id' => $municipalityId, 'code' => $parish['code']],
                    ['name' => $parish['name'], 'active' => true],
                );
            }
        }
    }
}
