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
        if ($pensum->subjects()->exists()) {
            return false;
        }

        $pensum->delete();

        return true;
    }
}
