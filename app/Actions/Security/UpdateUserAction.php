<?php

namespace App\Actions\Security;

use App\Enums\EducationalLevel;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;

class UpdateUserAction
{
    /**
     * Update the user's profile, sync their roles, and ensure sub-records exist.
     */
    public function handle(User $user, UserWrapper $wrapper): User
    {
        $user->update($wrapper->getUpdateData());

        $user->syncRoles($wrapper->getRoles());

        foreach ($wrapper->getRoles() as $roleName) {
            $this->ensureSubRecord($user, $roleName);
        }

        return $user;
    }

    /**
     * Ensure the role-specific sub-record exists for the user.
     * Creates it if not present, using firstOrCreate to avoid duplicates.
     */
    private function ensureSubRecord(User $user, string $roleName): void
    {
        match ($roleName) {
            'Profesor' => Professor::firstOrCreate(['user_id' => $user->id]),
            'Estudiante' => Student::firstOrCreate(
                ['user_id' => $user->id],
                ['educational_level' => EducationalLevel::University]
            ),
            'Representante' => Guardian::firstOrCreate(['user_id' => $user->id]),
            default => null,
        };
    }
}
