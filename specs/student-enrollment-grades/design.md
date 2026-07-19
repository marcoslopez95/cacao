# Design — student-enrollment-grades

## Backend

### 1. Extraer cálculo de nota final a un servicio compartido

Hoy `App\Http\Resources\Student\GradeCardResource::calculateFinalGrade` (privado) es la única implementación. Se extrae a:

`app/Services/Grade/FinalGradeCalculator.php`

```php
class FinalGradeCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $slotData  shape: [slot_id, weight, value, is_remedial, ...]
     */
    public function calculate(array $slotData, GradeConfig $config): ?string
    {
        // misma lógica que hoy: pondera no-remediales por weight/100,
        // si hay nota de reparación toma el máximo entre definitiva y reparación.
    }
}
```

`GradeCardResource` pasa a inyectar/instanciar el servicio y llamarlo en vez de su método privado. Sin cambio de comportamiento — mismo test `GradeCalculationTest.php` debe seguir pasando sin tocarlo.

### 2. Promedio del periodo por inscripción

Nuevo método en el mismo servicio (o un segundo servicio pequeño `app/Services/Grade/EnrollmentAverageCalculator.php` — se decide durante implementación según qué tan cohesivo quede) que, dado un `Enrollment` con `details.subject` y `details.gradeEntries.children` cargados, calcula por cada `EnrollmentDetail` su `final_grade` (reusando `FinalGradeCalculator` + resolución de `GradeConfig`, mismo patrón que `GradeCardResource::resolveConfig`) y devuelve el promedio simple de los `final_grade` no nulos. `null` si ninguna materia tiene nota definitiva.

Para evitar duplicar `resolveConfig`, se extrae también ese método del `GradeCardResource` al mismo servicio, o el servicio recibe el `GradeConfig` ya resuelto por materia — se decide en implementación priorizando no duplicar código.

### 3. `StudentShowResource`

- `enrollments[]` gana `period_average` (string|null) — llamando al cálculo del punto 2 por cada `Enrollment` (ya viene cargado con `details.subject`, hay que agregar `details.gradeEntries.children` al eager load en `StudentController::show`).
- `cumulative_gpa` deja de ser `$this->cumulative_gpa` y pasa a ser el promedio simple de los `period_average` no nulos de `enrollments[]` ya calculados (evitar recalcular dos veces).

### 4. Nueva ruta de detalle

`routes/web.php`, dentro del grupo `academic`:

```php
Route::get('students/{student}/enrollments/{enrollment}', [StudentController::class, 'showEnrollment'])
    ->name('students.enrollments.show');
```

`Academic\StudentController::showEnrollment(Student $student, Enrollment $enrollment)`:

```php
public function showEnrollment(Student $student, Enrollment $enrollment): Response
{
    abort_unless($enrollment->student_id === $student->id, 404);
    $this->authorize('view', $enrollment);

    $enrollment->load(['period', 'details.subject', 'details.gradeEntries.children']);

    return Inertia::render('admin/Students/EnrollmentGrades', [
        'student' => ['id' => $student->id, 'name' => $student->user->name],
        'grades' => (new GradeCardResource($enrollment, GradeVisibility::RealTime))->toArray(request()),
    ]);
}
```

`Gate::before` (admin bypass) ya cubre la autorización para el rol admin; `EnrollmentPolicy::view` no necesita cambios.

## Frontend

### `resources/js/types/studentShow.ts`

```ts
export interface EnrollmentHistoryItem {
    id: number
    period_name: string | null
    status: 'draft' | 'confirmed' | 'approved' | 'rejected' | null
    uc_inscritas: number
    uc_disponibles: number
    period_average: string | null   // nuevo
}
```

`cumulative_gpa` en `StudentShowData` no cambia de tipo (sigue `string | null`), solo cambia cómo se calcula en el backend.

### `resources/js/pages/admin/Students/Show.vue`

- Tabla "Historial de inscripciones": nueva columna `Promedio del periodo` (`{{ enrollment.period_average ?? '—' }}`) y una columna de acción con `<Link>` "Ver detalle" a la nueva ruta Wayfinder `students.enrollments.show({ student: student.id, enrollment: enrollment.id })`.
- Card "Promedio acumulado": sin cambios de template — ya muestra `student.cumulative_gpa`, que ahora viene calculado correctamente del backend.

### Nueva página `resources/js/pages/admin/Students/EnrollmentGrades.vue`

Mismo desglose que `student/Grades/Index.vue` (subject → slots → children, `final_grade`, `passed`), pero:
- Reutiliza el tipo `StudentGradeCard`/`StudentGradeSubject`/`StudentGradeSlot` ya existentes en `types/grade-entry.ts` — no se crean tipos nuevos.
- Estilo Tailwind consistente con `admin/Students/Show.vue` (clases `rounded-xl border border-[var(--border)] bg-[var(--bg-surface)]`), no el inline-style de `student/Grades/Index.vue`.
- Breadcrumb: Académico → Estudiantes → `{student.name}` → `{período}`.
- Botón "Volver" hacia `students.show({ student: student.id })`.

## Tests

- `tests/Feature/Academic/StudentControllerTest.php` (o el test existente que cubra `StudentController::show`, verificar nombre real): casos con notas completas, parciales y sin notas → `period_average` y `cumulative_gpa` correctos.
- Nuevo `tests/Feature/Academic/StudentEnrollmentGradesTest.php`: 200 + datos correctos para inscripción propia del estudiante de la ruta; 404 si la inscripción no le pertenece; admin puede ver cualquiera (Gate::before).
- `tests/Feature/Professor/GradeCalculationTest.php` no debe cambiar de comportamiento tras extraer `FinalGradeCalculator` — correrlo como regresión.
