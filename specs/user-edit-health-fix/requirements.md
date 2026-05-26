# Requirements — user-edit-health-fix

**Feature ID:** `user-edit-health-fix`
**QA Hallazgos:** HLZ-10, HLZ-15
**Prioridad:** ALTA — guardar S05 destruye activamente `blood_type_id`, `disability_type_id`, `insurance_type_id` existentes en DB

---

## Problema

### HLZ-10 / HLZ-15 — Round-trip roto en S05 Salud (todos los roles)

`HealthProfileResource` ya expone los campos planos `blood_type_id`, `disability_type_id` e `insurance_type_id` (siempre presentes, sin necesidad de nested relations). El bug está completamente en el frontend:

**Reload (carga inicial):**
- `buildInitialFormData()` lee `h.blood_type?.name` (objeto anidado ausente porque `UserController::edit()` no carga `healthProfile.bloodType` ni similares)
- El campo anidado no existe → `undefined`
- Los tres dropdowns de catálogo muestran vacío aunque haya datos en DB

**Save (guardar):**
- `saveHealth()` envía `blood_type: formData.bloodType ?? null` — clave sin `_id`, string
- `StoreHealthProfileRequest` espera `blood_type_id`, `disability_type_id`, `insurance_type_id`
- Las claves enviadas (`blood_type`, `disability_type`, `insurance_type`) no coinciden con las reglas → son ignoradas silenciosamente
- El backend no actualiza esos campos → los valores existentes en DB quedan INTACTOS...
- Pero como los campos reciben `null` (porque `formData.bloodType` es `undefined`), si el FormRequest tiene reglas `nullable` que aceptan null explícito, el valor se destruye

**Diagnóstico preciso (HLZ-15):** `HealthProfileResource::resolve()` devuelve `blood_type_id: 1` (campo plano presente) pero `buildInitialFormData()` nunca lee ese campo plano. Solo busca el objeto anidado ausente. La corrección es **frontend-only** — no requiere cambiar el Resource ni el eager-loading en el Controller.

**Confirmado (auditoría 2026-05-26):** Afecta todos los roles (admin, student, professor, guardian). User 137: `blood_type_id=5`, `insurance_type_id=4` en DB → vacíos en reload del formulario.

---

## Catálogos necesarios

S05 actualmente no tiene catálogos en `catalogData`. Los tres selects usan arrays locales hardcodeados. Se deben agregar:
- `bloodTypes` — `BloodType::active()->ordered()->get(['id', 'name', 'code'])`
- `disabilityTypes` — `DisabilityType::active()->ordered()->get(['id', 'name', 'code'])`
- `insuranceTypes` — `InsuranceType::active()->ordered()->get(['id', 'name', 'code'])`

Los modelos `BloodType`, `DisabilityType`, `InsuranceType` ya existen en `app/Models/Catalogs/`.

---

## Precondiciones

- `HealthProfileResource` ya expone `blood_type_id`, `disability_type_id`, `insurance_type_id` como campos planos (confirmado)
- `StoreHealthProfileRequest` ya tiene reglas `blood_type_id`, `disability_type_id`, `insurance_type_id` con `nullable` + `exists:*,id`
- Las tablas `blood_types`, `disability_types`, `insurance_types` existen y tienen datos

---

## Criterios de aceptación

1. **RF-01** — Al cargar la página de edición de un usuario con `blood_type_id` guardado en DB, el select de tipo de sangre en S05 muestra la opción correcta (no vacío).
2. **RF-02** — Al cargar la página de edición de un usuario con `disability_type_id` guardado en DB, el select de tipo de discapacidad muestra la opción correcta.
3. **RF-03** — Al cargar la página de edición de un usuario con `insurance_type_id` guardado en DB, el select de seguro médico muestra la opción correcta.
4. **RF-04** — Guardar S05 con un `blood_type_id` seleccionado → el valor persiste en DB (`health_profiles.blood_type_id` es el ID correcto tras el save).
5. **RF-05** — Guardar S05 sin cambiar los selects de catálogo NO destruye los `*_id` existentes en DB.
6. **RF-06** — Los dropdowns de S05 usan `catalogData.bloodTypes`, `catalogData.disabilityTypes`, `catalogData.insuranceTypes` con IDs de DB (no arrays locales hardcodeados).
7. **RF-07** — Tests en `UserEditUseCaseTest.php` verifican que guardar S05 con IDs de catálogo persiste correctamente.

---

## Archivos afectados

**Backend (PHP):**
- `app/Http/Controllers/Security/UserController.php` — agregar `BloodType`, `DisabilityType`, `InsuranceType` a `catalogData`

**Frontend (TypeScript):**
- `resources/js/types/userEdit.ts` — agregar `bloodTypes`, `disabilityTypes`, `insuranceTypes` a `UserFormCatalogData`
- `resources/js/types/userForm.ts` — agregar `bloodTypeId?`, `disabilityTypeId?`, `insuranceTypeId?` (number); marcar o eliminar los campos string `bloodType`, `disabilityType`, `insuranceType`

**Frontend (Composable):**
- `resources/js/composables/forms/useUserEditForm.ts`:
  - `buildInitialFormData()` bloque `healthProfile` (~línea 462): leer `h.blood_type_id`, `h.disability_type_id`, `h.insurance_type_id` (campos planos)
  - `saveHealth()` (~línea 168): enviar `blood_type_id`, `disability_type_id`, `insurance_type_id` (con sufijo `_id`, valores numéricos)

**Frontend (Vue):**
- `resources/js/components/security/UserForm/UserFormS05Health.vue` — cambiar los tres selects para usar `catalogData.bloodTypes`, `catalogData.disabilityTypes`, `catalogData.insuranceTypes` con `:value="item.id"`

**Tests:**
- `tests/Feature/Security/UserEditUseCaseTest.php` — agregar test S05 con IDs de catálogo
