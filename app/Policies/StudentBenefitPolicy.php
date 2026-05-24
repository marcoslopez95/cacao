<?php

namespace App\Policies;

use App\Models\StudentBenefit;
use App\Models\User;

class StudentBenefitPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function delete(User $user, StudentBenefit $studentBenefit): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }
}
