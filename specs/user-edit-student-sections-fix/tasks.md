# Tasks — user-edit-student-sections-fix

**Feature:** `user-edit-student-sections-fix`
**Scope:** Backend (1 controller) + Tipos TS (2 archivos) + Composable + 3 Componentes Vue + Tests
**Depends on:** ninguno (independiente)
**HLZs resueltos:** HLZ-11 (S11), HLZ-12 (S12), HLZ-13 (S14)
**HLZ-26 deferred:** workDial cosmético — no implementado en esta feature

---

## Task 01 — Backend: agregar catálogos de S11/S12/S14 a catalogData

- [x] Abrir `app/Http/Controllers/Security/UserController.php`
- [x] Agregar imports (solo los que no estén ya):
  ```php
  use App\Models\Catalogs\IncomeRange;
  use App\Models\Catalogs\IncomeSource;
  use App\Models\Catalogs\EmploymentType;
  use App\Models\Catalogs\LivingArrangement;
  use App\Models\Catalogs\HouseholdHeadType;
  use App\Models\Catalogs\HousingType;
  use App\Models\Catalogs\TenureType;
  use App\Models\Catalogs\ConstructionMaterial;
  use App\Models\Catalogs\CommuteTime;
  use App\Models\Catalogs\TransportType;
  ```
- [x] En el bloque `$props['catalogData']`, agregar:
  ```php
  // S11 (maritalStatuses ya está — reusar para guardian_marital_status_id)
  'livingArrangements'   => LivingArrangement::active()->ordered()->get(['id', 'name', 'code']),
  'householdHeadTypes'   => HouseholdHeadType::active()->ordered()->get(['id', 'name', 'code']),
  // S12
  'incomeRanges'         => IncomeRange::active()->ordered()->get(['id', 'name', 'code']),
  'incomeSources'        => IncomeSource::active()->ordered()->get(['id', 'name', 'code']),
  'employmentTypes'      => EmploymentType::active()->ordered()->get(['id', 'name', 'code']),
  // S14
  'housingTypes'          => HousingType::active()->ordered()->get(['id', 'name', 'code']),
  'tenureTypes'           => TenureType::active()->ordered()->get(['id', 'name', 'code']),
  'constructionMaterials' => ConstructionMaterial::active()->ordered()->get(['id', 'name', 'code']),
  'commuteTimes'          => CommuteTime::active()->ordered()->get(['id', 'name', 'code']),
  'transportTypes'        => TransportType::active()->ordered()->get(['id', 'name', 'code']),
  ```
- [x] Ejecutar `vendor/bin/sail bin pint --dirty --format agent` sobre `UserController.php`

**Acceptance:** `props.catalogData.livingArrangements`, `incomeRanges`, `housingTypes` son arrays no vacíos al cargar la página.

---

## Task 02 — Tipos TS: UserFormCatalogData (userEdit.ts)

- [x] Abrir `resources/js/types/userEdit.ts`
- [x] En la interface `UserFormCatalogData`, agregar:
  ```typescript
  livingArrangements:    Array<{ id: number; name: string; code: string }>
  householdHeadTypes:    Array<{ id: number; name: string; code: string }>
  incomeRanges:          Array<{ id: number; name: string; code: string }>
  incomeSources:         Array<{ id: number; name: string; code: string }>
  employmentTypes:       Array<{ id: number; name: string; code: string }>
  housingTypes:          Array<{ id: number; name: string; code: string }>
  tenureTypes:           Array<{ id: number; name: string; code: string }>
  constructionMaterials: Array<{ id: number; name: string; code: string }>
  commuteTimes:          Array<{ id: number; name: string; code: string }>
  transportTypes:        Array<{ id: number; name: string; code: string }>
  ```

**Acceptance:** Sin errores TypeScript relacionados con estas propiedades.

---

## Task 03 — Tipos TS: UserFormData (userForm.ts)

- [x] Abrir `resources/js/types/userForm.ts`
- [x] Agregar campos:
  ```typescript
  // S11
  repMaritalId?:      number
  livingId?:          number
  householdHeadId?:   number
  // S12
  incomeRangeId?:     number
  incomeSourceId?:    number
  remitFromId?:       number
  employmentTypeId?:  number
  // S14
  housingId?:         number
  tenureId?:          number
  constructionId?:    number
  commuteId?:         number
  transportId?:       number
  ```

**Acceptance:** Sin errores TypeScript en el composable al usar los nuevos campos.

---

## Task 04 — Composable: buildInitialFormData() — S11, S12, S14

- [x] Abrir `resources/js/composables/forms/useUserEditForm.ts`
- [x] **S11** — bloque `if (s.familyProfile)`:
  - `d.repMaritalId = f.guardian_marital_status_id ?? undefined`
  - `d.livingId = f.living_arrangement_id ?? undefined`
  - `d.householdHeadId = f.household_head_type_id ?? undefined`
- [x] **S12** — bloque `if (s.socioeconomicProfile)`:
  - `d.incomeRangeId = e.income_range_id ?? undefined`
  - `d.incomeSourceId = e.income_source_id ?? undefined`
  - `d.remitFromId = e.remittance_country_id ?? undefined`
  - `d.employmentTypeId = e.employment_type_id ?? undefined`
  - `d.socioDate = e.study_date?.substring(0, 10) ?? undefined` (normalizar datetime → date)
- [x] **S14** — bloque `if (s.housingProfile)`:
  - `d.housingId = h.housing_type_id ?? undefined`
  - `d.tenureId = h.tenure_type_id ?? undefined`
  - `d.constructionId = h.construction_material_id ?? undefined`
  - `d.commuteId = h.commute_time_id ?? undefined`
  - `d.transportId = h.transport_type_id ?? undefined`

**Acceptance:** Después del cambio, `formData.repMaritalId`, `formData.incomeRangeId`, `formData.housingId` tienen valores numéricos al cargar un estudiante con datos en DB.

---

## Task 05 — Composable: saveFamily(), saveSocioeconomic(), saveHousing() — claves con _id

- [x] **saveFamily()**: cambiar las tres claves de catálogo:
  - `guardian_marital_status_id: formData.repMaritalId ?? null`
  - `living_arrangement_id: formData.livingId ?? null`
  - `household_head_type_id: formData.householdHeadId ?? null`
- [x] **saveSocioeconomic()**: cambiar las cuatro claves de catálogo:
  - `income_range_id: formData.incomeRangeId ?? null`
  - `income_source_id: formData.incomeSourceId ?? null`
  - `remittance_country_id: formData.remitFromId ?? null`
  - `employment_type_id: formData.employmentTypeId ?? null`
  - Mantener `study_date: formData.socioDate ?? null` (string YYYY-MM-DD ya válido para Laravel `'date'`)
- [x] **saveHousing()**: cambiar las cinco claves de catálogo:
  - `housing_type_id: formData.housingId ?? null`
  - `tenure_type_id: formData.tenureId ?? null`
  - `construction_material_id: formData.constructionId ?? null`
  - `commute_time_id: formData.commuteId ?? null`
  - `transport_type_id: formData.transportId ?? null`

**Acceptance:** Los payloads de los tres handlers incluyen claves con sufijo `_id` y valores numéricos o null.

---

## Task 06 — Componente UserFormS11Family.vue: selects con catalogData

- [x] Abrir `resources/js/components/security/UserForm/UserFormS11Family.vue`
- [x] Verificar que recibe `catalogData` como prop; si no, agregarlo
- [x] Reemplazar selects de `guardian_marital_status`, `living_arrangement`, `household_head_type` con:
  - `catalogData.maritalStatuses` para guardian_marital_status (reusar el key ya existente)
  - `catalogData.livingArrangements` para living_arrangement
  - `catalogData.householdHeadTypes` para household_head_type
- [x] Bind con IDs: `setField('repMaritalId', Number($event.target.value) || undefined)`

**Acceptance:** Al cargar un estudiante con `guardian_marital_status_id` en DB, el select de estado civil muestra la opción correcta.

---

## Task 07 — Componente UserFormS12Socioeconomic.vue: selects con catalogData

- [x] Abrir `resources/js/components/security/UserForm/UserFormS12Socioeconomic.vue`
- [x] Verificar que recibe `catalogData` como prop; si no, agregarlo
- [x] Reemplazar selects de `income_range`, `income_source`, `employment_type` con `catalogData.incomeRanges`, `catalogData.incomeSources`, `catalogData.employmentTypes`
- [x] Para `remittance_country`: usar `catalogData.countries` (ya disponible) con bind a `remitFromId`
- [x] Bind de IDs: `setField('incomeRangeId', Number($event.target.value) || undefined)`
- [x] Verificar que el campo `study_date` usa `:value="data.socioDate"` (formato YYYY-MM-DD)

**Acceptance:** Al cargar un estudiante con `income_range_id` en DB, el select muestra la opción correcta.

---

## Task 08 — Componente UserFormS14Housing.vue: selects con catalogData

- [x] Abrir `resources/js/components/security/UserForm/UserFormS14Housing.vue`
- [x] Verificar que recibe `catalogData` como prop; si no, agregarlo
- [x] Reemplazar los cinco selects (housing_type, tenure_type, construction_material, commute_time, transport_type) con:
  - `catalogData.housingTypes`, `catalogData.tenureTypes`, `catalogData.constructionMaterials`, `catalogData.commuteTimes`, `catalogData.transportTypes`
- [x] Bind de IDs: `setField('housingId', Number($event.target.value) || undefined)`
- [x] Servicios básicos (`svc_water`, `svc_elec`, `svc_gas`, `svc_inet`) no cambian

**Acceptance:** Guardar S14 con un tipo de vivienda seleccionado persiste `housing_type_id` en DB correctamente.

---

## Task 09 — Tests: S11, S12, S14

- [x] Abrir `tests/Feature/Security/UserEditUseCaseTest.php`
- [x] Agregar test S11: `it('saves family profile with catalog IDs')` — PUT family-profile con `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id` → aserta HTTP 200 + persiste IDs en `family_profiles`
- [x] Agregar test S12: `it('saves socioeconomic profile with catalog IDs and normalizes study_date')` — PUT socioeconomic-profile con `income_range_id`, `employment_type_id`, `study_date: '2026-01-15'` → aserta HTTP 200 + persiste IDs + date correcto
- [x] Agregar test S14: `it('saves housing profile with catalog IDs')` — PUT housing-profile con `housing_type_id`, `tenure_type_id` → aserta HTTP 200 + persiste IDs en `housing_profiles`
- [x] Ejecutar `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest`

**Acceptance:** Los tres tests nuevos pasan. Suite completa `UserEditUseCaseTest` sigue en verde.

---

## Task 10 — Build y verificación final

- [x] `vendor/bin/sail npm run build` — sin errores TypeScript
- [x] `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest`
- [x] Confirmar 0 errores y 0 fallos

**Acceptance:** Build limpio. Todos los tests pasan.
