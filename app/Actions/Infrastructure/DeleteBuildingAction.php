<?php

namespace App\Actions\Infrastructure;

use App\Models\Building;

class DeleteBuildingAction
{
    public function handle(Building $building): bool
    {
        if ($building->classrooms()->exists()) {
            return false;
        }

        $building->delete();

        return true;
    }
}
