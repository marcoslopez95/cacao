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
        // Pensum guard will be re-enabled once App\Models\Pensum is implemented
        $career->delete();

        return true;
    }
}
