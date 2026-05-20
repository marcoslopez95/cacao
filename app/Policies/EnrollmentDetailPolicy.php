<?php

namespace App\Policies;

use App\Models\EnrollmentDetail;
use App\Models\User;

class EnrollmentDetailPolicy
{
    /**
     * Determine whether the user can view a specific enrollment detail.
     */
    public function view(User $user, EnrollmentDetail $detail): bool
    {
        return $user->can('view', $detail->enrollment);
    }

    /**
     * Determine whether the user can update a specific enrollment detail.
     */
    public function update(User $user, EnrollmentDetail $detail): bool
    {
        return $user->can('update', $detail->enrollment);
    }

    /**
     * Determine whether the user can delete a specific enrollment detail.
     */
    public function delete(User $user, EnrollmentDetail $detail): bool
    {
        return $user->can('update', $detail->enrollment);
    }
}
