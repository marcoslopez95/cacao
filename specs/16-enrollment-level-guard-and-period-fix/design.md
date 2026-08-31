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

## Corrección al diseño (2026-08-30) — `BuildEnrollmentCatalogAction` sí requiere cambios

**Hallazgo del `tester` en modo `pre`, verificado en DB:** la afirmación original de este documento ("el catálogo deja de estar vacío sin cambios aquí") era incorrecta. `BuildEnrollmentCatalogAction` resuelve secciones vía `Subject::sections()` (`HasMany`, FK directa `sections.subject_id`). Esa FK solo se usa para secciones `University` (1 sección = 1 materia). Las secciones `School` se crean con `subject_id = null` — una sola sección de grado (ej. "4to A") cubre **todas** las materias de ese grado simultáneamente, vinculadas por la tabla pivote `section_subjects` (`Section::sectionSubjects(): BelongsToMany`, sin relación inversa en `Subject` hoy). Confirmado en DB: 9 secciones escolares de Bachillerato, las 9 con `subject_id = null`; `Subject::sections()` devuelve 0 para cualquier materia de ese pensum. Sin este cambio, el fix de período (arriba) no alcanza para cerrar HLZ-43 — el período sería correcto pero el catálogo seguiría vacío.

### 4. `app/Models/Subject.php`

Agregar la relación inversa que falta:

```php
public function schoolSections(): BelongsToMany
{
    return $this->belongsToMany(Section::class, 'section_subjects');
}
```

### 5. `app/Actions/Enrollment/BuildEnrollmentCatalogAction.php`

Cargar también `schoolSections` con los mismos filtros que `sections`, y combinar ambas colecciones por materia:

```php
public function handle(Student $student, Period $period, Enrollment $draft): Collection
{
    $constraints = fn ($q) => $q
        ->where('period_id', $period->id)
        ->withCount([
            'enrollmentDetails as enrolled' => fn ($q) => $q->whereIn('status', [
                EnrollmentDetailStatus::Draft->value,
                EnrollmentDetailStatus::Confirmed->value,
            ]),
        ])
        ->with(['schedules.professor.user', 'theoryClassroom', 'labClassroom', 'mainTeacher.user']);

    $subjects = $student->pensum->subjects()
        ->with(['sections' => $constraints, 'schoolSections' => $constraints])
        ->orderBy('period_number')
        ->get();

    // ... completedSubjectIds, selectedSubjectIds sin cambios ...

    return $subjects
        ->filter(fn ($subject) => $subject->sections->isNotEmpty() || $subject->schoolSections->isNotEmpty())
        ->map(function ($subject) use ($student, $completedSubjectIds, $selectedSubjectIds) {
            $allSections = $subject->sections->concat($subject->schoolSections);
            $selectedDetail = $selectedSubjectIds->get($subject->id);

            return [
                'subject' => $subject,
                'prereqs_ok' => $this->validator->canTake($student, $subject),
                'completed' => in_array($subject->id, $completedSubjectIds),
                'recommended_trim' => $subject->period_number === $student->academic_year,
                'selected_section_id' => $selectedDetail?->section_id,
                'selected_detail_id' => $selectedDetail?->id,
                'sections' => $allSections,
            ];
        })
        ->values();
}
```

No hace falta preocuparse por duplicados: una sección `University` nunca tiene filas en `section_subjects` (solo se pueblan para secciones `School` en el seeder), así que `sections` y `schoolSections` son mutuamente excluyentes en la práctica.

## Sin cambios en

- `StoreEnrollmentRequest::authorize()` — ya delega correctamente a `$this->user()->can('create', ...)`, se beneficia del fix en la Policy sin tocarlo.
- Migraciones — no se agrega ninguna columna ni tabla nueva (`section_subjects` ya existe).
