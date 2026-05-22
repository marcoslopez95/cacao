# Tasks — Enrollment Backend

**Feature:** `01-enrollment-backend`
**Plan fuente:** `docs/superpowers/plans/2026-05-20-enrollment-backend.md`

---

## Progreso general

- [x] Task 1 — Migrations & Models: Student & Guardian
- [x] Task 2 — Migrations & Models: Enrollment & EnrollmentDetail
- [x] Task 3 — Services: PrerequisiteValidator
- [x] Task 4 — Services: EnrollmentCacheManager & EnrollmentService
- [x] Task 5 — Policies & Authorization
- [x] Task 6 — Form Requests & Resources
- [x] Task 7 — Wrappers
- [x] Task 8 — Actions
- [x] Task 9 — Controller & Endpoints
- [x] Task 10 — Feature Tests

---

## Detalle de cada task

---

### Task 1 — Migrations & Models: Student & Guardian ✅

**Archivos involucrados:**
- `database/migrations/*_create_students_table.php`
- `database/migrations/*_create_guardians_table.php`
- `database/migrations/*_add_guardian_id_to_students.php`
- `app/Models/Student.php`
- `app/Models/Guardian.php`
- `app/Models/User.php` (relaciones añadidas)

**Criterio de done:**
- Tablas `students` y `guardians` existen en BD con FKs RESTRICT correctas
- `Student` tiene relaciones: `user()`, `guardian()`, `pensum()`, `enrollments()`
- `Guardian` tiene relaciones: `user()`, `students()`
- `User` tiene relaciones: `student()`, `guardian()`
- Migraciones corren sin error en BD limpia

---

### Task 2 — Migrations & Models: Enrollment & EnrollmentDetail ✅

**Archivos involucrados:**
- `database/migrations/*_create_enrollments_table.php`
- `database/migrations/*_create_enrollment_details_table.php`
- `app/Models/Enrollment.php`
- `app/Models/EnrollmentDetail.php`

**Criterio de done:**
- Tablas `enrollments` y `enrollment_details` existen con FKs RESTRICT y constraints `unique_student_period` / `unique_enrollment_subject`
- `Enrollment` tiene: scopes `draftDetails()`, `confirmedDetails()`, relaciones `student()`, `period()`, `pensum()`, `details()`
- `EnrollmentDetail` tiene relaciones: `enrollment()`, `subject()`, `section()`
- `status` enum consistente: `draft/confirmed/approved/rejected` en enrollments; `draft/confirmed/rejected` en enrollment_details

---

### Task 3 — Services: PrerequisiteValidator ✅

**Archivos involucrados:**
- `app/Services/Enrollment/PrerequisiteValidator.php`
- `tests/Unit/Enrollment/PrerequisiteValidatorTest.php`

**Criterio de done:**
- `canTake(Student, Subject): bool` devuelve `true` si no hay prelaciones
- `canTake(Student, Subject): bool` devuelve `false` si alguna prelación no está aprobada
- `getMissingPrerequisites(Student, Subject): array` devuelve IDs de materias faltantes
- Tests unitarios pasan: `vendor/bin/sail artisan test --compact --filter=PrerequisiteValidatorTest`

---

### Task 4 — Services: EnrollmentCacheManager & EnrollmentService ✅

**Archivos involucrados:**
- `app/Services/Enrollment/EnrollmentCacheManager.php`
- `app/Services/Enrollment/EnrollmentService.php`
- `tests/Unit/Enrollment/EnrollmentServiceTest.php`

**Criterio de done:**
- `EnrollmentCacheManager` gestiona claves Redis: cupo por sección (TTL 30s), prelaciones por materia (TTL 3600s), pensum activo por estudiante (TTL 1800s)
- `EnrollmentService::validateAddSubject()` retorna `['valid' => bool, 'error' => ?string]`
- Tests unitarios pasan: `vendor/bin/sail artisan test --compact --filter=EnrollmentServiceTest`

---

### Task 5 — Policies & Authorization ✅

**Archivos involucrados:**
- `app/Policies/EnrollmentPolicy.php`
- `app/Policies/EnrollmentDetailPolicy.php`
- `app/Providers/AppServiceProvider.php` (policies registradas)

**Criterio de done:**
- `EnrollmentPolicy` implementa: `viewAny`, `view`, `create`, `update`, `confirm`, `delete`
- `Student` puede ver/modificar solo su propia inscripción
- `Guardian` puede ver/modificar inscripciones de sus estudiantes asignados
- Ambas policies registradas en `AppServiceProvider`
- Sin `if ($user->role === ...)` en ningún controlador

---

### Task 6 — Form Requests & Resources ← EN PROGRESO

**Archivos involucrados:**
- `app/Http/Requests/Enrollment/StoreEnrollmentRequest.php`
- `app/Http/Requests/Enrollment/StoreEnrollmentDetailRequest.php`
- `app/Http/Resources/Enrollment/EnrollmentResource.php`
- `app/Http/Resources/Enrollment/EnrollmentDetailResource.php`

**Criterio de done:**
- `StoreEnrollmentRequest::authorize()` usa Policy (no lógica inline)
- `StoreEnrollmentRequest::rules()` valida `student_id` nullable con `exists:students,id`
- `StoreEnrollmentDetailRequest::authorize()` verifica `$user->can('update', $enrollment)`
- `StoreEnrollmentDetailRequest::rules()` valida `subject_id` y `section_id` requeridos con exists
- `EnrollmentResource` incluye: id, student (id + name), period, pensum, uc_disponibles, uc_inscritas, status, details (colección)
- `EnrollmentDetailResource` incluye: id, subject (id + code + name + credits_uc), section (id + code + capacity), status
- Pint sin errores: `vendor/bin/sail bin pint --dirty --format agent`

---

### Task 7 — Wrappers

**Archivos involucrados:**
- `app/Http/Wrappers/Enrollment/EnrollmentWrapper.php`
- `app/Http/Wrappers/Enrollment/EnrollmentDetailWrapper.php`

**Criterio de done:**
- Ambos Wrappers extienden `Illuminate\Support\Collection`
- `EnrollmentWrapper::getStudent(): Student` resuelve al estudiante correcto (del request o del usuario autenticado)
- `EnrollmentDetailWrapper::getSubject(): Subject` y `getSection(): Section` resuelven via `findOrFail()`
- Sin lógica de negocio en los Wrappers — solo resolución de modelos
- Pint sin errores

---

### Task 8 — Actions

**Archivos involucrados:**
- `app/Actions/Enrollment/CreateEnrollmentAction.php`
- `app/Actions/Enrollment/AddEnrollmentDetailAction.php`
- `app/Actions/Enrollment/ConfirmEnrollmentAction.php`

**Criterio de done:**
- Cada Action tiene exactamente un método `handle()`
- `CreateEnrollmentAction::handle(Student): Enrollment` crea cabecera para el período activo
- `AddEnrollmentDetailAction::handle(Enrollment, Subject, Section): EnrollmentDetail` crea detalle y decrementa caché
- `ConfirmEnrollmentAction::handle(Enrollment): Enrollment` usa `DB::transaction()` + `lockForUpdate()` sobre secciones, revalida cupos y prelaciones, actualiza todos los detalles y la cabecera
- Sin lógica de validación inline en Actions (delegan a Services)
- Pint sin errores

---

### Task 9 — Controller & Endpoints

**Archivos involucrados:**
- `app/Http/Controllers/Enrollment/EnrollmentController.php`
- `routes/web.php` (5 rutas añadidas)

**Criterio de done:**
- Controller tiene exactamente 5 métodos: `index`, `store`, `addDetail`, `removeDetail`, `confirm`
- Cada método tiene máximo 8 líneas de código
- No hay lógica de negocio en el Controller (todo delegado a Services/Actions)
- Rutas nombradas: `enrollment.index`, `enrollment.store`, `enrollment.detail.store`, `enrollment.detail.destroy`, `enrollment.confirm`
- `vendor/bin/sail artisan route:list | grep enrollment` muestra las 5 rutas
- Pint sin errores

---

### Task 10 — Feature Tests

**Archivos involucrados:**
- `tests/Feature/Enrollment/EnrollmentControllerTest.php`

**Criterio de done:**
- Mínimo 10 casos de prueba cubriendo: student view, guardian view, cross-student restriction, create enrollment, add subject, duplicate subject rejection, remove subject, confirm enrollment, confirm empty rejection, guardian enroll assigned, guardian reject unassigned
- Todos los tests pasan: `vendor/bin/sail artisan test --compact --filter=EnrollmentControllerTest`
- Suite completa pasa: `vendor/bin/sail artisan test --compact`
