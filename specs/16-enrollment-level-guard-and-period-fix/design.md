# HLZ-38 + HLZ-43 — Diseño del fix

## Decisión de diseño

Dos correcciones independientes que comparten archivo y dominio, por eso van en un solo feature:

1. **Guard de nivel** se implementa en dos capas: `EnrollmentPolicy::create()` (la fuente de verdad de autorización) **y** una llamada explícita a `Gate::authorize()` dentro de `EnrollmentController::index()` — porque hoy ese método crea el draft sin pasar por ningún Gate. Sin la segunda parte, arreglar solo la Policy no cambia nada en producción.
2. **Resolución de período por tipo** se hace con un solo `where('type', ...)` adicional en la misma query que ya existe — no se introduce ningún servicio nuevo. El único enum relevante hoy en DB es `Semester` y `Year` (confirmado con `Period::all()`: `trimester` como tipo de período standalone no se usa nunca; los "trimestres" viven como `Lapse` dentro de un período `Year`).

**Opción descartada:** resolver el período/lapso con un servicio dedicado (`PeriodResolver`). Se descarta porque la lógica cabe en una línea de `where()` — un servicio nuevo sería sobre-ingeniería para este alcance.

## Cambios necesarios

### 1. `app/Policies/EnrollmentPolicy.php`

```php
use App\Enums\EducationalLevel;

public function create(User $user, ?Student $student = null): bool
{
    if ($student) {
        $isOwnedByGuardian = $user->guardian?->students()
            ->where('id', $student->id)
            ->exists() ?? false;

        return $isOwnedByGuardian && $student->educational_level !== EducationalLevel::University;
    }

    return $user->student && $user->student->educational_level === EducationalLevel::University;
}
```

### 2. `app/Http/Controllers/Enrollment/EnrollmentController.php`

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $student = $this->resolveStudent($user, $request->integer('student_id') ?: null);

    Gate::authorize('create', [Enrollment::class, $student]);

    $period = $student
        ? Period::where('status', PeriodStatus::Active)
            ->where('type', $student->educational_level === EducationalLevel::University
                ? PeriodType::Semester
                : PeriodType::Year)
            ->first()
        : null;

    // ... resto sin cambios
}
```

`resolveStudent()` no cambia — sigue resolviendo el estudiante objetivo (propio o del representante). El `Gate::authorize()` se agrega inmediatamente después, antes del `firstOrCreate`.

`buildRules()`:

```php
private function buildRules(?Period $period, ?Student $student): array
{
    $periodLabel = $student?->academic_year ? "{$student->academic_year}er trimestre" : '';

    if ($period?->type === PeriodType::Year) {
        $lapse = $period->lapses()
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $periodLabel = $lapse?->name ?? $periodLabel;
    }

    return [
        'period' => $period?->name,
        // ...
        'trimester' => $periodLabel,
    ];
}
```

### 3. `resources/js/pages/student/Dashboard.vue`

Condicionar el CTA de inscripción existente (`"Ir a inscripciones →"`) a `props.student.educational_level === 'university'`. Cuando no aplique, mostrar un texto explicativo: "Tu representante debe inscribirte." El dato `educational_level` ya debe estar disponible en las props del dashboard (verificar en `Student\DashboardController::index()`; si no está, agregarlo al payload de Inertia).

## Sin cambios en

- `StoreEnrollmentRequest::authorize()` — ya delega correctamente a `$this->user()->can('create', ...)`, se beneficia del fix en la Policy sin tocarlo.
- `BuildEnrollmentCatalogAction` — sigue filtrando secciones por `period_id`; al llegar el `$period` correcto (anual para escolares), el catálogo deja de estar vacío sin cambios aquí.
- Migraciones — no se agrega ninguna columna ni tabla nueva.
