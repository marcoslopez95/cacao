<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;

class UpdateBuildingAction
{
    public function handle(Building $building, BuildingWrapper $wrapper): Building
    {
        $building->update(['name' => $wrapper->getName()]);

        return $building;
    }
}
