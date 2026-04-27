<?php

namespace App\Actions\Infrastructure;

use App\Models\Classroom;

class DeleteClassroomAction
{
    public function handle(Classroom $classroom): void
    {
        $classroom->delete();
    }
}
