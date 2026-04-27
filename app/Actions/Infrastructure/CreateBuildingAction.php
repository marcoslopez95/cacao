<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;

class CreateBuildingAction
{
    public function handle(BuildingWrapper $wrapper): Building
    {
        return Building::create(['name' => $wrapper->getName()]);
    }
}
