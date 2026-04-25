<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\CoordinationWrapper;
use App\Models\Coordination;

class CreateCoordinationAction
{
    /**
     * Create a new coordination.
     */
    public function handle(CoordinationWrapper $wrapper): Coordination
    {
        return Coordination::create($wrapper->getStoreData());
    }
}
