# Requirements — user-edit-student-sections-fix

**Feature ID:** `user-edit-student-sections-fix`
**QA Hallazgos:** HLZ-11 (S11 Familia), HLZ-12 (S12 Socioeconómico), HLZ-13 (S14 Vivienda), HLZ-26 (S16 cosmético — incluido como deferred)
**Prioridad:** ALTA — datos de S11 y S12 nunca persisten; S14 save silenciosamente inefectivo

---

## Problema

### HLZ-11 — S11 Familia: round-trip completamente roto

**Reload:** `FamilyProfileResource` ya expone `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id` como campos planos (confirmado en el resource). Sin embargo, `buildInitialFormData()` lee `f.guardian_marital_status?.name`, `f.living_arrangement?.name`, `f.household_head_type?.name` (objetos anidados ausentes). Los tres selects aparecen vacíos aunque DB tenga valores.

**Save:** `saveFamily()` envía claves sin sufijo `_id`:
- `guardian_marital_status: formData.repMarital ?? null` — string
- `living_arrangement: formData.living ?? null` — string
- `household_head_type: formData.householdHead ?? null` — string

`StoreFamilyProfileRequest` espera `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id`. Las claves no coinciden → los FK IDs nunca se actualizan.

**Catálogos faltantes en catalogData:** `maritalStatuses`, `livingArrangements`, `householdHeadTypes`.

### HLZ-12 — S12 Socioeconómico: round-trip completamente roto

**Reload:** `SocioeconomicProfileResource` ya expone `income_range_id`, `income_source_id`, `employment_type_id`, `remittance_country_id` como campos planos. `buildInitialFormData()` lee los objetos anidados ausentes → los cuatro selects vacíos en reload.

**Save:** `saveSocioeconomic()` envía:
- `income_range: formData.incomeRange ?? null` — string
- `income_source: formData.incomeSource ?? null` — string
- `employment_type: formData.employmentType ?? null` — string
- `remittance_country: formData.remitFrom ?? null` — string (nombre de país)

`StoreSocioeconomicProfileRequest` espera `income_range_id`, `income_source_id`, `employment_type_id`, `remittance_country_id` (los primeros tres son tablas de catálogo; `remittance_country_id` es FK a `countries`). Las claves no coinciden → los FK IDs nunca se actualizan.

**Bug adicional en study_date:** `SocioeconomicProfileResource` devuelve `study_date` como objeto Carbon serializado → `"2026-05-07 00:00:00"` (datetime). `buildInitialFormData()` asigna directamente este string. `input[type=date]` requiere `YYYY-MM-DD` exacto. En algunos navegadores el campo queda vacío o mal interpretado.

**Catálogos faltantes en catalogData:** `incomeRanges`, `incomeSources`, `employmentTypes` (para los campos FK de catálogo). `remittance_country_id` usa `countries` que ya está en `catalogData`.

### HLZ-13 — S14 Vivienda: save silenciosamente inefectivo (reload funciona)

**Save:** `saveHousing()` envía claves sin sufijo `_id`:
- `housing_type: formData.housing ?? null` — string
- `tenure_type: formData.tenure ?? null` — string
- `construction_material: formData.construction ?? null` — string
- `commute_time: formData.commute ?? null` — string
- `transport_type: formData.transport ?? null` — string

`StoreHousingProfileRequest` espera `housing_type_id`, `tenure_type_id`, `construction_material_id`, `commute_time_id`, `transport_type_id`. Las claves no coinciden → save responde 200 pero los FK IDs no se actualizan. Los servicios básicos sí persisten correctamente (usan su propio mecanismo de sync).

**Reload:** A diferencia de S11/S12, el reload SÍ funciona porque `UserController::edit()` ya eager-load las relaciones anidadas del housing profile (`housingType`, `tenureType`, etc.) y el resource devuelve los objetos con `.name`. `buildInitialFormData()` los lee correctamente como strings de nombre para mostrar en el select.

**Catálogos faltantes en catalogData:** `housingTypes`, `tenureTypes`, `constructionMaterials`, `commuteTimes`, `transportTypes`. Actualmente `buildInitialFormData()` lee los `.name` del objeto anidado para inicializar los selects — hay que cambiar a IDs con `catalogData`.

---

## HLZ-26 — Deferred (cosmético)

`workDial` en S16 no tiene columna en DB — siempre resetea a +58. Se marca como `deferred` y no se implementa en esta feature. La institución es venezolana, +58 es correcto en el 99% de los casos.

---

## Precondiciones

- Los Resources (`FamilyProfileResource`, `SocioeconomicProfileResource`, `HousingProfileResource`) ya exponen los `*_id` como campos planos
- Los FormRequests (`StoreFamilyProfileRequest`, `StoreSocioeconomicProfileRequest`, `StoreHousingProfileRequest`) ya tienen reglas con sufijo `_id` correctas
- `countries` ya está en `catalogData` (útil para `remittance_country_id` en S12)
- Los modelos de catálogo `IncomeRange`, `IncomeSource`, `EmploymentType`, `HousingType`, `TenureType`, `ConstructionMaterial`, `CommuteTime`, `TransportType`, `LivingArrangement`, `HouseholdHeadType` existen en `app/Models/Catalogs/`
- `MaritalStatus` ya está siendo usado por S16 (`guardianMaritalStatuses` en catalogData) — verificar nombre exacto del campo

---

## Criterios de aceptación

### S11 Familia
1. **RF-01** — Al recargar S11 de un estudiante con `guardian_marital_status_id` en DB, el select muestra el valor correcto.
2. **RF-02** — Al recargar S11 de un estudiante con `living_arrangement_id` en DB, el select muestra el valor correcto.
3. **RF-03** — Al recargar S11 de un estudiante con `household_head_type_id` en DB, el select muestra el valor correcto.
4. **RF-04** — Guardar S11 con un `guardian_marital_status_id` seleccionado persiste el ID correcto en `family_profiles.guardian_marital_status_id`.

### S12 Socioeconómico
5. **RF-05** — Al recargar S12 de un estudiante con `income_range_id` en DB, el select muestra el valor correcto.
6. **RF-06** — Al recargar S12 de un estudiante con `employment_type_id` en DB, el select muestra el valor correcto.
7. **RF-07** — Guardar S12 con `income_range_id` seleccionado persiste el ID en `socioeconomic_profiles.income_range_id`.
8. **RF-08** — El campo `study_date` en S12 se pre-llena en formato `YYYY-MM-DD` (no como datetime completo).

### S14 Vivienda
9. **RF-09** — Guardar S14 con un tipo de vivienda seleccionado persiste `housing_profiles.housing_type_id` con el ID correcto.
10. **RF-10** — Guardar S14 sin cambios no altera los `*_id` existentes en DB.

---

## Archivos afectados

**Backend (PHP):**
- `app/Http/Controllers/Security/UserController.php` — agregar catálogos a `catalogData`
- `app/Http/Controllers/Security/UserController.php` — el eager-loading de `housingProfile` puede simplificarse (nested relations ya no son necesarias si usamos IDs planos para los selects; sin embargo, para no romper otros usos, se mantienen y además se agregan los catálogos)

**Frontend (TypeScript):**
- `resources/js/types/userEdit.ts` — agregar nuevas propiedades a `UserFormCatalogData`
- `resources/js/types/userForm.ts` — agregar campos `*Id` para S11, S12, S14

**Frontend (Composable):**
- `resources/js/composables/forms/useUserEditForm.ts`:
  - `buildInitialFormData()`: S11 (~línea 521), S12 (~línea 531), S14 (~línea 553)
  - `saveFamily()` (~línea 229): claves con `_id`
  - `saveSocioeconomic()` (~línea 242): claves con `_id`, `study_date` normalizado
  - `saveHousing()` (~línea 280): claves con `_id`

**Frontend (Vue):**
- `resources/js/components/security/UserForm/UserFormS10Languages.vue` — no afectado
- `resources/js/components/security/UserForm/UserFormS13Benefits.vue` — no afectado (estos ya usan IDs)
- Componentes S11, S12, S14 a identificar por nombres exactos en `resources/js/components/security/UserForm/`

**Tests:**
- `tests/Feature/Security/UserEditUseCaseTest.php`
