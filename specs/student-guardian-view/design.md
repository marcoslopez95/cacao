# Design — student-guardian-view

## Backend

`app/Http/Controllers/Student/DashboardController.php::index`

- `$student->guardians()->with('user')->get()` — pivot ya trae `kinship_type_id`, `is_primary`, `is_emergency_contact`.
- Resolver parentesco con `KinshipType::pluck('name', 'id')` (mismo patrón que `App\Http\Resources\Academic\GuardianShowResource`) — una sola query de catálogo.
- Ordenar `is_primary` desc antes de mapear.
- Mapear a array plano (no se crea un Resource nuevo — el resto del controller ya devuelve arrays inline a `Inertia::render`, se sigue esa convención):

```php
'guardians' => $student->guardians()->with('user')->get()
    ->sortByDesc(fn ($g) => (int) $g->pivot->is_primary)
    ->map(fn ($g) => [
        'name' => $g->user->name,
        'email' => $g->user->email,
        'phone' => $g->user->phone_primary,
        'kinship' => $kinshipNames->get($g->pivot->kinship_type_id),
        'is_primary' => (bool) $g->pivot->is_primary,
    ])->values(),
```

## Frontend

`resources/js/types/student-dashboard.ts` — agregar:

```ts
export interface StudentGuardianSummary {
  name: string
  email: string
  phone: string | null
  kinship: string | null
  is_primary: boolean
}
```

Agregar `guardians: StudentGuardianSummary[]` a `StudentDashboardProps`.

`resources/js/pages/student/Dashboard.vue` — nueva sección `<template v-if="props.guardians.length">` con título "Mi representante" (singular si length===1, "Mis representantes" si >1), tarjetas en el mismo estilo inline (`--bg-surface`, `--border`, `--radius-lg`) que las demás secciones del dashboard — sin introducir un sistema de componentes nuevo, coherente con el resto del archivo. Badge "Principal" cuando `is_primary`.

Ubicación en el template: después del stats row, antes del bloque `Today's timeline`.

## Tests

`tests/Feature/Student/DashboardTest.php` — agregar:
- dashboard incluye `guardians` (array) en el caso ya existente de "returns 200 with all required props".
- caso con 2 representantes (uno `is_primary`) → primero en el array es el principal.
- caso sin representantes → `guardians` es `[]`.
