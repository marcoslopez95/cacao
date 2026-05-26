# Requirements — user-edit-professor-s17-fix

**Feature ID:** `user-edit-professor-s17-fix`
**QA Hallazgos:** HLZ-20 (CRITICO), HLZ-21 (MEDIA), HLZ-04 (BAJA)
**Prioridad:** CRITICA — crash 500 cuando is_coordinator=true bloquea completamente S17

---

## Problema

### HLZ-20 — Crash 500 al guardar S17 con is_coordinator=true (CRITICO)

`StoreStaffProfileRequest::rules()` valida `coordinated_department_id` con `exists:departments,id`. La tabla `departments` **no existe** en el schema real — la tabla correcta se llama `coordinations`. Cuando el validador ejecuta la regla `exists`, lanza `SQLSTATE[42P01]: Undefined table: departments` → HTTP 500.

Adicionalmente, `StaffProfile::coordinatedDepartment()` referencia `Department::class` que no existe como clase PHP. Si se intenta eager-load esta relación, Laravel lanza `Class "App\Models\Department" not found`.

### HLZ-21 — Fechas de staff profile aparecen vacías en el formulario (MEDIA)

`StaffProfileResource` aplica el cast `'date'` de Eloquent. Carbon serializa fechas como ISO 8601 (`"2014-09-09T00:00:00.000000Z"`) en JSON. `buildInitialFormData()` asigna estos strings directamente a `d.hireDate`, `d.endDate`, `d.coordSince`. Los `input[type=date]` del HTML spec requieren exactamente el formato `YYYY-MM-DD` — el formato ISO 8601 es rechazado silenciosamente y el campo se muestra vacío aunque el dato exista en DB.

### HLZ-04 — PHPDoc ausente en StaffProfile (BAJA)

No hay comentario PHPDoc que documente que `staff_profiles.professor_id` es la FK al usuario (indirecta via `professors.user_id`). Un buscador directo de `StaffProfile::where('user_id', ...)` falla con `Undefined column`.

---

## Precondiciones

- El modelo `Coordination` existe en `app/Models/Coordination.php`
- `catalogData.departments` en `UserController::edit()` ya usa `Coordination::where('active', true)` — correcto
- Los tests de `StaffProfileTest.php` existen y cubren casos básicos de S17

---

## Criterios de aceptación

1. **RF-01** — Guardar S17 con `is_coordinator=true` y un `coordinated_department_id` válido (que corresponde a una fila existente en `coordinations`) devuelve HTTP 200, no 500 ni 422.
2. **RF-02** — Eager-load de `StaffProfile::coordinatedDepartment()` no lanza ninguna excepción PHP.
3. **RF-03** — Al cargar la página de edición de un profesor con `hire_date`, `termination_date` o `coordinator_since` guardados en DB, los campos date en el formulario muestran los valores en formato `YYYY-MM-DD` (no vacíos).
4. **RF-04** — Los campos de fecha no se destruyen al guardar S17 sin modificarlos: el valor en DB permanece igual antes y después del save.
5. **RF-05** — El modelo `StaffProfile` documenta con PHPDoc que la FK es `professor_id` (no `user_id`) y que la relación indirecta al usuario es `professor.user`.
6. **RF-06** — Los tests de `StaffProfileTest.php` incluyen un caso `is_coordinator=true` con `coordinated_department_id` válido que pasa en verde.

---

## Archivos afectados

**Backend (PHP):**
- `app/Http/Requests/Admin/StoreStaffProfileRequest.php` — línea 33: `exists:departments,id` → `exists:coordinations,id`
- `app/Models/StaffProfile.php` — método `coordinatedDepartment()`: reemplazar `Department::class` por `Coordination::class` + agregar `use App\Models\Coordination;` + agregar PHPDoc de clase

**Frontend (TypeScript/Vue):**
- `resources/js/composables/forms/useUserEditForm.ts` — `buildInitialFormData()` líneas 587-592: normalizar las tres fechas con `.substring(0, 10)`

**Tests:**
- `tests/Feature/Admin/StaffProfileTest.php` — agregar test de caso `is_coordinator=true` con `coordinated_department_id` válido
