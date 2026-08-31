# HLZ-44 + HLZ-46 — Diseño

## Decisiones de diseño

**HLZ-44 — sin columna nueva.** Se descartó agregar `schedule_id` a `class_sessions` (que `design.md` de `15-attendance-module` documentaba pero nunca se implementó). No hace falta: la restricción es "¿el momento actual cae dentro de algún horario real de esta sección?", una pregunta que se responde consultando `Schedule::where('section_id', ...)` directamente, sin vincular la sesión a una fila de horario específica. Vincularla añadiría complejidad (qué pasa si el horario cambia después, qué `Schedule` elegir si hay varios el mismo día) sin necesidad real.

**HLZ-44 — el guard vive en la Policy, no en el FormRequest.** Mismo patrón que el resto del módulo (pertenencia de sección ya vive en `ClassSessionPolicy`). Se extrae el cálculo de ventana horaria a un método privado reutilizable, replicando la lógica ya probada en `Professor\DashboardController::index()`.

**HLZ-46 — cancelar no borra `AttendanceRecord`.** Si se cancela una sesión `held`, sus registros de asistencia quedan como historial (nadie los borra). Es la interpretación más segura de "cualquier estado" — preserva datos reales tomados en clase, y sigue permitiendo que la sesión (ahora `cancelled`) sirva de target para Recuperación.

**HLZ-46 — no se puede cancelar una sesión `advanced`, `recovered` o ya `cancelled`.** Esos son estados "consumidos" (ya sustituidos por otra sesión, o ya en su estado terminal) — cancelar ahí no tiene un significado claro. Interpretación razonable dentro de la decisión "cualquier estado" (que apuntaba específicamente al caso `held`, no a estos).

## Cambios necesarios

### 1. `app/Policies/ClassSessionPolicy.php`

```php
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
    // viewAny() y update() sin cambios

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

    public function cancel(User $user, ClassSession $classSession): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $classSession->section->main_teacher_id === $professor->id;
    }

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
```

### 2. `app/Http/Requests/Professor/StoreClassSessionRequest.php` y `AdminStoreClassSessionRequest.php`

```php
// ANTES
return $this->user()?->can('create', [ClassSession::class, $section]) ?? false;

// DESPUÉS
return $this->user()?->can('create', [ClassSession::class, $section, $this->input('type')]) ?? false;
```

### 3. `app/Actions/Attendance/CancelClassSessionAction.php` (nuevo)

```php
<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use Illuminate\Validation\ValidationException;

class CancelClassSessionAction
{
    public function handle(ClassSession $classSession): ClassSession
    {
        if (! in_array($classSession->status, [ClassSessionStatus::Scheduled, ClassSessionStatus::Held], true)) {
            throw ValidationException::withMessages([
                'status' => ['Esta sesión no se puede cancelar.'],
            ]);
        }

        $classSession->update(['status' => ClassSessionStatus::Cancelled]);

        return $classSession;
    }
}
```

### 4. Rutas (`routes/web.php`)

```php
// dentro del grupo professor/sections
Route::patch('sections/{section}/attendance/sessions/{classSession}/cancel', [Professor\AttendanceController::class, 'cancelSession'])
    ->name('sections.attendance.sessions.cancel');

// dentro del grupo admin/sections
Route::patch('sections/{section}/attendance/sessions/{classSession}/cancel', [AdminAttendanceController::class, 'cancelSession'])
    ->name('sections.attendance.sessions.cancel');
```

### 5. `Professor\AttendanceController::cancelSession()` y `Admin\AttendanceController::cancelSession()`

```php
public function cancelSession(Section $section, ClassSession $classSession, CancelClassSessionAction $action): RedirectResponse
{
    Gate::authorize('cancel', $classSession);
    $action->handle($classSession);
    Inertia::flash('toast', ['type' => 'success', 'message' => 'Sesión cancelada.']);

    return to_route('professor.sections.attendance.index', $section); // 'admin....' en el controller de Admin
}
```

Sin FormRequest — no hay input de body, mismo patrón ya usado por `sheet()` en este archivo (`Gate::authorize()` directo en el controller).

### 6. Frontend — `resources/js/pages/professor/attendance/Index.vue` (y equivalente `admin/attendance/SectionIndex.vue`)

Botón "Cancelar" en cada fila de sesión, visible cuando `s.status === 'scheduled' || s.status === 'held'`, con confirmación (`confirm()` o modal ya usado en el sistema), disparando `router.patch` hacia la ruta nueva vía Wayfinder. Regenerar Wayfinder tras agregar la ruta.

## Sin cambios en

- `CreateClassSessionAction`, `CreateMakeupSessionAction`, `CreateAdvanceSessionAction` — sin relación con este alcance.
- `AttendanceSheetResource`, `ClassSessionResource` — ya exponen `status`, suficiente para la UI condicional.
- Migraciones — sin columnas ni tablas nuevas.
