<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\CoordinationWrapper;
use App\Models\Coordination;

class UpdateCoordinationAction
{
    /**
     * Update the given coordination with the wrapper data.
     */
    public function handle(Coordination $coordination, CoordinationWrapper $wrapper): Coordination
    {
        $coordination->update($wrapper->getUpdateData());

        return $coordination;
    }
}
