# Design — student-academic-show

**Feature ID:** `student-academic-show`

---

## Decisiones técnicas

### UC-02 Botón Editar — wiring frontend puro

Agregar `user_id` a `StudentListResource` y `StudentListItem`, luego en `Index.vue` importar la función Wayfinder de `UserController::edit` y añadir `:href` al botón lápiz en los cuatro layouts de tabla.

```typescript
// Index.vue — import
import { edit as editUser } from '@/actions/App/Http/Controllers/Security/UserController'

// En el template (los cuatro layouts)
<Link :href="editUser({ user: s.user_id })" ...>
```

Wayfinder genera URLs con type safety: `edit({ user: 5 })` → `{ url: '/security/users/5/edit', method: 'get' }`.

### UC-01 Botón Ver — nueva ruta + show() + resource + página Vue

**Patrón de arquitectura:** `Controller → Resource → Page.vue` (lectura pura — sin Action ni Wrapper).

No se necesita Action ni Wrapper porque `show()` es read-only. No hay efectos secundarios ni datos a transformar complejos — solo carga y proyección.

#### Nueva ruta

```php
// routes/web.php — dentro del grupo academic.
Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
```

Colocar antes de la ruta `students` del index para que Wayfinder la genere correctamente.

#### `StudentController::show()`

Carga eager con relaciones necesarias para evitar N+1:

```php
public function show(Student $student): Response
{
    $student->load([
        'user',
        'pensum.career',
        'academicStatus',
        'modality',
        'shift',
        'guardians.user',
        'guardians' => fn ($q) => $q->withPivot(['kinship_type_id', 'is_primary']),
        'enrollments.period',
        'enrollments.details.section.subject',
        'enrollments.details.section.schedules',
        'enrollments.details.section.mainTeacher.user',
        'enrollments.details.gradeEntries' => fn ($q) => $q->published()->slotLevel(),
    ]);

    return Inertia::render('admin/Students/Show', [
        'student' => new StudentShowResource($student),
    ]);
}
```

#### `StudentShowResource` — shape de datos

```php
// app/Http/Resources/Academic/StudentShowResource.php
[
    'id'               => $this->id,
    'user_id'          => $this->user_id,
    // Identidad (desde user)
    'name'             => $this->user->name,
    'email'            => $this->user->email,
    'cedula'           => $this->user->document ?? null,   // cuando user-profiles esté implementado
    'student_code'     => $this->student_code,
    'educational_level' => $this->educational_level,
    // Carrera / pensum
    'career_name'      => $this->pensum?->career?->name,
    'pensum_name'      => $this->pensum?->name,
    'pensum_total_credits' => null,  // pendiente: campo aún no calculado
    // Datos académicos
    'academic_year'    => $this->academic_year,
    'academic_status'  => $this->academicStatus?->name,
    'modality'         => $this->modality?->name,
    'shift'            => $this->shift?->name,
    'cumulative_gpa'   => $this->cumulative_gpa,
    'enrollment_date'  => $this->enrollment_date?->format('Y-m-d'),
    // Inscripción activa (la más reciente con período activo)
    'active_enrollment' => ...,   // ver abajo
    // Representantes
    'guardians'        => GuardianSummaryResource::collection($this->guardians),
    // Historial de inscripciones
    'enrollments'      => EnrollmentHistoryResource::collection($this->enrollments),
]
```

**Inscripción activa:** en el Resource, filtrar `$this->enrollments` para encontrar la del período activo, o simplemente serializar todas y dejar al frontend mostrar la primera (más reciente). Simplicidad: serializar todas y en Vue mostrar la primera del tope del historial como "activa".

**Alternativa más limpia:** pasar `active_enrollment` como el enrollment cuyo `period.status = 'active'`, y `enrollments` como el historial completo. Se resuelve en el Resource con una colección filtrada:

```php
'active_enrollment' => $this->enrollments
    ->filter(fn ($e) => $e->period?->status?->value === 'active')
    ->map(fn ($e) => [
        'period_name'   => $e->period->name,
        'status'        => $e->status->value,
        'uc_inscritas'  => $e->uc_inscritas,
        'uc_disponibles' => $e->uc_disponibles,
    ])
    ->first(),
```

#### Página `admin/Students/Show.vue`

Layout de secciones verticales (sin tabs — el contenido es read-only y no hay que navegar). Estructura visual:

```
┌─ Header (nombre, nivel, estado académico, botón Volver + botón Editar)
├─ Sección: Identidad (email, cédula, código)
├─ Sección: Carrera y Pensum (nombre carrera, pensum, año, modalidad, turno)
├─ Sección: Inscripción activa (estado, UC) — colapsada si no hay
├─ Sección: Representantes — solo visible si educational_level != 'university'
├─ Sección: Historial de inscripciones (tabla: período / estado / UC)
├─ Sección: Notas (promedio acumulado + tabla por período)
└─ Sección: Secciones actuales con horario (tabla: materia / código / profesor / días/horas)
```

**Breadcrumbs:**
```
Académico > Estudiantes > {nombre del estudiante}
```

**Botón Editar en Show.vue:** reutiliza Wayfinder `editUser({ user: student.user_id })` — igual que en Index.vue.

---

## Archivos a crear

| Archivo | Tipo | Descripción |
|---|---|---|
| `app/Http/Resources/Academic/StudentShowResource.php` | PHP Resource | Proyección completa del perfil |
| `resources/js/pages/admin/Students/Show.vue` | Vue page | Página de perfil read-only |
| `resources/js/types/studentShow.ts` | TypeScript types | Mirror del StudentShowResource |

## Archivos a modificar

| Archivo | Cambio |
|---|---|
| `routes/web.php` | Agregar `Route::get('students/{student}', ...)` → `academic.students.show` |
| `app/Http/Controllers/Academic/StudentController.php` | Agregar método `show()` |
| `app/Http/Resources/Academic/StudentListResource.php` | Agregar campo `user_id` |
| `resources/js/types/student.ts` | Agregar campo `user_id: number` a `StudentListItem` |
| `resources/js/pages/admin/Students/Index.vue` | Importar Wayfinder y añadir `:href` a botones Ver y Editar (4 layouts) |
| `tests/Feature/Academic/StudentControllerTest.php` | Agregar tests del método `show()` |

---

## Notas de implementación

- `StudentShowResource` **no** necesita resource anidado para `guardians` — un inline `map()` es suficiente dado que los datos son simples (nombre + relación)
- Los horarios de secciones se muestran como "Lunes 7:00–9:00" — formato en Vue, no en Resource
- `cumulative_gpa` puede ser `null` si el estudiante no tiene notas aún — mostrar "—"
- `user->document` depende de que `user-profiles` esté implementado; si no existe el campo, usar `null` con seguridad (`$this->user->document ?? null`)
- Wayfinder debe regenerarse después de agregar la ruta: `vendor/bin/sail artisan wayfinder:generate`
- No se necesita Policy en el controlador porque la ruta ya está protegida por `auth` middleware y solo el admin accede al módulo academic — si se quiere ser estricto, aplicar `Gate::authorize('viewAny', Student::class)` en `show()` igual que en el resto del controlador
