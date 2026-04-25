<?php

namespace App\Actions\Security;

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use Illuminate\Support\Facades\DB;

class AssignCoordinatorAction
{
    /**
     * Close the current active assignment and create a new one for the given user.
     */
    public function handle(Coordination $coordination, int $userId, int $assignedById): CoordinationAssignment
    {
        return DB::transaction(function () use ($coordination, $userId, $assignedById): CoordinationAssignment {
            $coordination->assignments()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            return $coordination->assignments()->create([
                'user_id' => $userId,
                'assigned_by' => $assignedById,
                'assigned_at' => now(),
            ]);
        });
    }
}
