<?php

namespace App\Actions\Scheduling;

use App\Models\Lapse;

class DeleteLapseAction
{
    public function handle(Lapse $lapse): bool
    {
        return (bool) $lapse->delete();
    }
}
