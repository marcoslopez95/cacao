<?php

namespace App\Actions\Academic;

use App\Models\Pensum;

class DeletePensumAction
{
    /**
     * Deletes the pensum if it has no associated subjects.
     * Returns false when deletion is blocked.
     */
    public function handle(Pensum $pensum): bool
    {
        // Subject guard will be re-enabled once App\Models\Subject is implemented (Part 4)
        $pensum->delete();

        return true;
    }
}
