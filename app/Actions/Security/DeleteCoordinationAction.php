<?php

namespace App\Actions\Security;

use App\Models\Coordination;

class DeleteCoordinationAction
{
    /**
     * Delete the coordination if it has no active coordinator.
     *
     * Returns false when an active coordinator is assigned, so the controller
     * can flash the appropriate error message without throwing an exception.
     */
    public function handle(Coordination $coordination): bool
    {
        if ($coordination->currentAssignment()->exists()) {
            return false;
        }

        $coordination->delete();

        return true;
    }
}
