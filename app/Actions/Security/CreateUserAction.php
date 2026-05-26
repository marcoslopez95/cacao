<?php

namespace App\Actions\Security;

use App\Enums\EducationalLevel;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class CreateUserAction
{
    /**
     * Create a new user, assign their role, and optionally send a password reset link.
     */
    public function handle(UserWrapper $wrapper): User
    {
        $user = User::create($wrapper->getStoreData());

        $user->syncRoles([$wrapper->getRoleName()]);

        $this->ensureSubRecord($user, $wrapper->getRoleName());

        if ($wrapper->sendsResetLink()) {
            Password::sendResetLink(['email' => $wrapper->getEmail()]);
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
