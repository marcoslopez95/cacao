<?php

use App\Models\Catalogs\Gender;
use App\Observers\CatalogObserver;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    (new UserProfileCatalogsSeeder)->run();
    Cache::flush();
    Gender::observe(CatalogObserver::class);
});

it('cached() returns collection keyed by code', function () {
    $cached = Gender::cached();

    expect($cached)->toBeInstanceOf(Collection::class)
        ->and($cached->count())->toBe(4)
        ->and($cached->has('male'))->toBeTrue()
        ->and($cached->has('female'))->toBeTrue()
        ->and($cached->has('non_binary'))->toBeTrue()
        ->and($cached->has('prefer_not_to_say'))->toBeTrue();
});

it('saving a catalog record clears the cache', function () {
    Cache::put('catalog.genders', 'stale', 3600);

    Gender::first()->save();

    expect(Cache::has('catalog.genders'))->toBeFalse();
});

it('deleting a catalog record clears the cache', function () {
    $gender = Gender::create([
        'code' => 'test_delete',
        'name' => 'Test Delete',
        'active' => true,
        'sort_order' => 99,
    ]);

    Cache::put('catalog.genders', 'stale', 3600);

    $gender->delete();

    expect(Cache::has('catalog.genders'))->toBeFalse();
});
