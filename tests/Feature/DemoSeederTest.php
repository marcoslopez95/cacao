<?php

use App\Models\Building;
use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Subject;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds academic structure', function () {
    (new DemoSeeder)->run();

    expect(CareerCategory::count())->toBeGreaterThanOrEqual(3);
    expect(Career::count())->toBeGreaterThanOrEqual(5);
    expect(Pensum::count())->toBeGreaterThanOrEqual(5);
    expect(Subject::count())->toBeGreaterThanOrEqual(40);
});

it('seeds infrastructure', function () {
    (new DemoSeeder)->run();

    expect(Building::count())->toBeGreaterThanOrEqual(2);
    expect(Classroom::count())->toBeGreaterThanOrEqual(15);
});
