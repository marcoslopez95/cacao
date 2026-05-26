# Tasks — user-edit-professor-s17-fix

**Feature:** `user-edit-professor-s17-fix`
**Scope:** Backend (1 FormRequest + 1 Model) + Frontend (1 composable) + Tests
**Depends on:** ninguno (independiente)
**HLZs resueltos:** HLZ-20 (CRITICO), HLZ-21 (MEDIA), HLZ-04 (BAJA)

---

## Task 01 — Fix StoreStaffProfileRequest: departments → coordinations

- [x] Abrir `app/Http/Requests/Admin/StoreStaffProfileRequest.php`
- [x] Cambiar `'exists:departments,id'` a `'exists:coordinations,id'` en la regla de `coordinated_department_id`
- [x] Verificar que el resto de reglas no menciona `departments`

**Acceptance:** `Validator::make(["coordinated_department_id" => $validCoordinationId], [...rules...])` no lanza QueryException y pasa la validación.

---

## Task 02 — Fix StaffProfile::coordinatedDepartment(): Department → Coordination

- [x] Abrir `app/Models/StaffProfile.php`
- [x] Agregar `use App\Models\Coordination;` en el bloque de imports
- [x] Reemplazar `Department::class` por `Coordination::class` en el método `coordinatedDepartment()`
- [x] Eliminar el comentario obsoleto "Relation will be wired once departments table exists."
- [x] Eliminar la directiva `@phpstan-ignore-next-line`
- [x] Agregar PHPDoc de clase documentando que la FK es `professor_id` (no `user_id`)
- [x] Agregar PHPDoc al método `coordinatedDepartment()` explicando que usa `coordinations` tabla

**Acceptance:** `StaffProfile::find($id)->coordinatedDepartment` no lanza ninguna excepción PHP.

---

## Task 03 — Fix buildInitialFormData(): normalizar fechas ISO a YYYY-MM-DD

- [x] Abrir `resources/js/composables/forms/useUserEditForm.ts`
- [x] Localizar el bloque `if (props.professor?.staffProfile)` en `buildInitialFormData()`
- [x] Cambiar las tres asignaciones de fecha:
  - `d.hireDate = p.hire_date?.substring(0, 10) ?? undefined`
  - `d.endDate = p.termination_date?.substring(0, 10) ?? undefined`
  - `d.coordSince = p.coordinator_since?.substring(0, 10) ?? undefined`

**Acceptance:** Al cargar la página de edición de un profesor con `hire_date` en DB, el campo `hireDate` en `formData` tiene el formato `YYYY-MM-DD`, no el ISO completo.

---

## Task 04 — Test: guardar S17 con is_coordinator=true y coordinated_department_id válido

- [x] Abrir `tests/Feature/Admin/StaffProfileTest.php`
- [x] Agregar un test `it('can save staff profile with is_coordinator true and valid coordination id')`
- [x] El test crea una fila en `coordinations` usando factory o `Coordination::create()`
- [x] Llama `PUT /academic/professors/{professor}/staff-profile` con payload incluido `is_coordinator: true` y `coordinated_department_id: $coordination->id`
- [x] Aserta HTTP 200 (no 422, no 500)
- [x] Aserta que `staff_profiles.is_coordinator = true` y `staff_profiles.coordinated_department_id = $coordination->id` en DB

**Acceptance:** `php artisan test --compact --filter=StaffProfileTest` pasa en verde incluyendo el nuevo test.

---

## Task 05 — Ejecutar pint y tests

- [x] `vendor/bin/sail bin pint --dirty --format agent` sobre `StoreStaffProfileRequest.php` y `StaffProfile.php`
- [x] `vendor/bin/sail artisan test --compact --filter=StaffProfileTest`
- [x] Confirmar 0 errores y tests en verde

**Acceptance:** Pint no reporta cambios. Tests pasan sin fallos.
