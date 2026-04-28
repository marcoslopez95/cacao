<?php

namespace App\Actions\Scheduling;

use App\Models\Professor;

class DeleteProfessorAction
{
    public function handle(Professor $professor): bool
    {
        return (bool) $professor->delete();
    }
}
