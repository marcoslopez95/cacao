<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserDocument;

class UserDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function delete(User $user, UserDocument $document): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function verify(User $user, UserDocument $document): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']) && $user->id !== $document->user_id;
    }
}
