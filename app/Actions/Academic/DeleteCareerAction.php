<?php

namespace App\Actions\Academic;

use App\Models\Career;

class DeleteCareerAction
{
    /**
     * Deletes the career if it has no associated pensums.
     * Returns false when deletion is blocked.
     */
    public function handle(Career $career): bool
    {
        if ($career->pensums()->exists()) {
            return false;
        }

        $career->delete();

        return true;
    }
}
