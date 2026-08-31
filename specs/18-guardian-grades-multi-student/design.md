# HLZ-40 + HLZ-41 — Diseño del fix

## Decisión de diseño

El filtro defensivo de nivel (HLZ-40) y el selector multi-estudiante (HLZ-41) tocan los mismos dos controllers (`Guardian\GradeController`, `Guardian\DashboardController`) — se implementan juntos.

**Opción elegida para HLZ-40:** filtro a nivel de query (`->where('educational_level', '!=', University)`) en los controllers de guardian, no un constraint en DB ni un scope global en el modelo `Guardian`. Se descarta un scope global porque `Guardian::students()` es una relación genérica reutilizable — forzar el filtro ahí podría ocultar el problema en contextos donde sí se necesite ver el vínculo crudo (ej. una futura pantalla admin de gestión de vínculos). El filtro vive en el punto de uso (los controllers de `/guardian/*`), que es exactamente donde HLZ-40 pide el guard defensivo.

## Cambios necesarios

### 1. `app/Http/Controllers/Guardian/GradeController.php`

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $guardian = $user->guardian;
    abort_unless($guardian !== null, 404);

    $eligibleStudents = $guardian->students()->where('educational_level', '!=', EducationalLevel::University);

    if ($studentId = $request->integer('student_id')) {
        $student = (clone $eligibleStudents)->find($studentId);
        abort_if(! $student, 403);
    } else {
        $student = $eligibleStudents->first();
        abort_unless($student !== null, 404);
    }

    // ... resto sin cambios (period, visibility, enrollment, render)

    return Inertia::render('guardian/Grades/Index', [
        'grades' => /* ... */,
        'period' => $period?->name,
        'student_name' => $student->user->name,
        'student_id' => $student->id,
        'students' => $guardian->students()
            ->where('educational_level', '!=', EducationalLevel::University)
            ->with('user:id,name')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->user->name]),
    ]);
}
```

Se agrega `students` a las props para alimentar el selector — mismo patrón que ya usa `guardian/Dashboard.vue`.

### 2. `app/Http/Controllers/Guardian/DashboardController.php`

```php
$studentsQuery = $guardian->students()
    ->where('educational_level', '!=', EducationalLevel::University)
    ->with(['user:id,name', 'pensum']);
```

Único cambio: agregar el `where()` antes del `with()`.

### 3. `resources/js/pages/guardian/Grades/Index.vue`

Agregar un selector (mismo componente `AppSelect` u equivalente ya usado en el sistema) visible solo cuando `props.students.length > 1`. Al cambiar la selección, `router.get(route('guardian.grades.index'), { student_id: selected })` (o vía Wayfinder). Cuando hay 1 solo estudiante, el selector no se renderiza — sin cambio visual respecto a hoy.

## Sin cambios en

- `EnrollmentController::resolveStudent()` — patrón de referencia, no se modifica.
- Tabla `student_guardians` — sin migración nueva.
- `GuardianPolicy` — sigue cubriendo solo `update()` del propio registro Guardian; el guard de nivel vive en los controllers, no en la Policy.
