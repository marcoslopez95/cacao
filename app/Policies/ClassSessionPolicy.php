<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\Section;
use App\Models\User;

class ClassSessionPolicy
{
    /**
     * Profesor puede ver sesiones de sus propias secciones.
     * Admin puede ver cualquiera (interceptado por Gate::before).
     */
    public function viewAny(User $user, Section $section): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $section->main_teacher_id === $professor->id;
    }

    /**
     * Profesor puede crear sesiones en sus propias secciones.
     * Admin puede crear en cualquier sección (interceptado por Gate::before).
     */
    public function create(User $user, Section $section): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $section->main_teacher_id === $professor->id;
    }

    /**
     * Profesor puede editar sesiones de sus propias secciones.
     * Admin puede editar cualquiera (interceptado por Gate::before).
     */
    public function update(User $user, ClassSession $classSession): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $classSession->section->main_teacher_id === $professor->id;
    }

    /**
     * Profesor puede pasar lista en sus propias secciones.
     * Admin puede pasar lista en cualquier sección (interceptado por Gate::before).
     */
    public function takeAttendance(User $user, ClassSession $classSession): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $classSession->section->main_teacher_id === $professor->id;
    }
}
