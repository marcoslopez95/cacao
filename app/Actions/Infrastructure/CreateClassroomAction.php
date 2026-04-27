<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Classroom;

class CreateClassroomAction
{
    public function handle(ClassroomWrapper $wrapper): Classroom
    {
        return Classroom::create([
            'building_id' => $wrapper->getBuildingId(),
            'identifier'  => $wrapper->getIdentifier(),
            'type'        => $wrapper->getType(),
            'capacity'    => $wrapper->getCapacity(),
        ]);
    }
}
