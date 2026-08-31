<?php

namespace App\Policies;

use App\Enums\ClassSessionType;
use App\Enums\DayOfWeek;
use App\Models\ClassSession;
use App\Models\Schedule;
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
     * Sesiones Regular exigen que el momento actual caiga dentro de un Schedule real de la sección.
     */
    public function create(User $user, Section $section, ?string $type = null): bool
    {
        $professor = $user->professor;

        if ($professor === null || $section->main_teacher_id !== $professor->id) {
            return false;
        }

        if ($type !== ClassSessionType::Makeup->value && $type !== ClassSessionType::Advance->value) {
            return $this->isWithinScheduleWindow($section);
        }

        return true;
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
     * Sesiones Regular exigen que el momento actual caiga dentro de un Schedule real de la sección.
     */
    public function takeAttendance(User $user, ClassSession $classSession): bool
    {
        $professor = $user->professor;

        if ($professor === null || $classSession->section->main_teacher_id !== $professor->id) {
            return false;
        }

        if ($classSession->type === ClassSessionType::Regular) {
            return $this->isWithinScheduleWindow($classSession->section);
        }

        return true;
    }

    /**
     * Profesor puede cancelar sesiones de sus propias secciones.
     * Admin puede cancelar cualquiera (interceptado por Gate::before).
     */
    public function cancel(User $user, ClassSession $classSession): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $classSession->section->main_teacher_id === $professor->id;
    }

    /**
     * Determina si el momento actual cae dentro de algún Schedule real de la sección
     * (día de la semana + hora).
     */
    private function isWithinScheduleWindow(Section $section): bool
    {
        $now = now();
        $today = DayOfWeek::tryFrom(strtolower($now->format('l')));

        if ($today === null) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return Schedule::where('section_id', $section->id)
            ->where('day_of_week', $today)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->exists();
    }
}
