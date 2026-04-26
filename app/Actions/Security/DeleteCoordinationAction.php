<?php

namespace App\Actions\Security;

use App\Models\Coordination;

class DeleteCoordinationAction
{
    /**
     * Delete the coordination if it has no assignments at all.
     *
     * Returns false when any assignment exists (active or historical), so the controller
     * can flash the appropriate error message without throwing an exception.
     */
    public function handle(Coordination $coordination): bool
    {
        if ($coordination->assignments()->exists()) {
            return false;
        }

        $coordination->delete();

        return true;
    }
}
