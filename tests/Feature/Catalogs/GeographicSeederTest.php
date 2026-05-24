<?php

use App\Models\Country;
use Database\Seeders\Catalogs\GeographicSeeder;

beforeEach(function () {
    (new GeographicSeeder)->run();
});

it('venezuela exists as a country', function () {
    expect(Country::where('iso2', 'VE')->exists())->toBeTrue();
});

it('there are 24 venezuelan states', function () {
    $venezuela = Country::where('iso2', 'VE')->firstOrFail();

    expect($venezuela->states()->count())->toBe(24);
});

it('every venezuelan state has at least one municipality', function () {
    $venezuela = Country::where('iso2', 'VE')->firstOrFail();

    $states = $venezuela->states()->get();

    foreach ($states as $state) {
        expect($state->municipalities()->count())->toBeGreaterThanOrEqual(1);
    }
});
