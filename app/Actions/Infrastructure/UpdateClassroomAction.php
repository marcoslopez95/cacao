<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Classroom;

class UpdateClassroomAction
{
    public function handle(Classroom $classroom, ClassroomWrapper $wrapper): Classroom
    {
        $classroom->update([
            'building_id' => $wrapper->getBuildingId(),
            'identifier'  => $wrapper->getIdentifier(),
            'type'        => $wrapper->getType(),
            'capacity'    => $wrapper->getCapacity(),
        ]);

        return $classroom;
    }
}
