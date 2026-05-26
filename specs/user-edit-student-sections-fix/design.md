# Design — user-edit-student-sections-fix

**Feature ID:** `user-edit-student-sections-fix`

---

## Patrón aplicado

Mismo patrón que `user-edit-catalog-ids` y `user-edit-health-fix`:
1. Backend agrega catálogos a `catalogData`
2. `buildInitialFormData()` lee IDs planos del resource (ya presentes)
3. Handlers de save envían claves con sufijo `_id` y valores numéricos
4. Componentes Vue usan `v-for` sobre `catalogData.*` con `:value="item.id"`

---

## Catálogos nuevos a agregar a catalogData

**Archivo:** `app/Http/Controllers/Security/UserController.php`

Nuevos imports necesarios (los que no están ya importados):
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

Nuevas entradas en `$props['catalogData']`:
```php
// S11 — Familia (MaritalStatus ya está como 'maritalStatuses' — usarlo para S11 también)
'livingArrangements' => LivingArrangement::active()->ordered()->get(['id', 'name', 'code']),
'householdHeadTypes' => HouseholdHeadType::active()->ordered()->get(['id', 'name', 'code']),

// S12 — Socioeconómico
'incomeRanges'    => IncomeRange::active()->ordered()->get(['id', 'name', 'code']),
'incomeSources'   => IncomeSource::active()->ordered()->get(['id', 'name', 'code']),
'employmentTypes' => EmploymentType::active()->ordered()->get(['id', 'name', 'code']),
// remittance_country_id ya usa 'countries' (ya en catalogData)

// S14 — Vivienda
'housingTypes'          => HousingType::active()->ordered()->get(['id', 'name', 'code']),
'tenureTypes'           => TenureType::active()->ordered()->get(['id', 'name', 'code']),
'constructionMaterials' => ConstructionMaterial::active()->ordered()->get(['id', 'name', 'code']),
'commuteTimes'          => CommuteTime::active()->ordered()->get(['id', 'name', 'code']),
'transportTypes'        => TransportType::active()->ordered()->get(['id', 'name', 'code']),
```

**Nota sobre S11 marital status:** `maritalStatuses` ya está en `catalogData` (fue agregado para S16 en `user-edit-catalog-ids`). S11 usa `guardian_marital_status_id` que también apunta a la tabla `marital_statuses`. Reusar el mismo key `maritalStatuses`.

---

## UserFormCatalogData: nuevas propiedades

**Archivo:** `resources/js/types/userEdit.ts`

```typescript
// S11
livingArrangements: Array<{ id: number; name: string; code: string }>
householdHeadTypes: Array<{ id: number; name: string; code: string }>

// S12
incomeRanges:    Array<{ id: number; name: string; code: string }>
incomeSources:   Array<{ id: number; name: string; code: string }>
employmentTypes: Array<{ id: number; name: string; code: string }>

// S14
housingTypes:          Array<{ id: number; name: string; code: string }>
tenureTypes:           Array<{ id: number; name: string; code: string }>
constructionMaterials: Array<{ id: number; name: string; code: string }>
commuteTimes:          Array<{ id: number; name: string; code: string }>
transportTypes:        Array<{ id: number; name: string; code: string }>
```

---

## UserFormData: nuevos campos *Id

**Archivo:** `resources/js/types/userForm.ts`

```typescript
// S11 — reemplazar campos string:
repMaritalId?:      number   // guardian_marital_status_id
livingId?:          number   // living_arrangement_id
householdHeadId?:   number   // household_head_type_id

// S12 — reemplazar campos string:
incomeRangeId?:     number   // income_range_id
incomeSourceId?:    number   // income_source_id
remitFromId?:       number   // remittance_country_id (FK a countries)
employmentTypeId?:  number   // employment_type_id

// S14 — reemplazar campos string:
housingId?:         number   // housing_type_id
tenureId?:          number   // tenure_type_id
constructionId?:    number   // construction_material_id
commuteId?:         number   // commute_time_id
transportId?:       number   // transport_type_id
```

---

## buildInitialFormData(): leer IDs planos

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

### S11 (bloque `if (s.familyProfile)`, ~línea 521):

```typescript
// ANTES (buggy — objetos anidados ausentes):
d.repMarital    = f.guardian_marital_status?.name ?? undefined
d.living        = f.living_arrangement?.name      ?? undefined
d.householdHead = f.household_head_type?.name     ?? undefined

// DESPUÉS (correcto — IDs planos del resource):
d.repMaritalId    = f.guardian_marital_status_id ?? undefined
d.livingId        = f.living_arrangement_id      ?? undefined
d.householdHeadId = f.household_head_type_id     ?? undefined
```

### S12 (bloque `if (s.socioeconomicProfile)`, ~línea 531):

```typescript
// ANTES:
d.incomeRange    = e.income_range?.name       ?? undefined
d.incomeSource   = e.income_source?.name      ?? undefined
d.remitFrom      = e.remittance_country?.name ?? undefined
d.employmentType = e.employment_type?.name    ?? undefined
d.socioDate      = e.study_date               ?? undefined

// DESPUÉS:
d.incomeRangeId    = e.income_range_id         ?? undefined
d.incomeSourceId   = e.income_source_id        ?? undefined
d.remitFromId      = e.remittance_country_id   ?? undefined
d.employmentTypeId = e.employment_type_id      ?? undefined
d.socioDate        = e.study_date?.substring(0, 10) ?? undefined  // normalizar datetime → date
```

### S14 (bloque `if (s.housingProfile)`, ~línea 553):

El reload de S14 ya funciona (lee objetos anidados con nombre). Pero para ser consistente con el patrón y para que el save funcione, cambiamos a IDs:

```typescript
// ANTES:
d.housing      = h.housing_type?.name          ?? undefined
d.tenure       = h.tenure_type?.name           ?? undefined
d.construction = h.construction_material?.name ?? undefined
d.commute      = h.commute_time?.name          ?? undefined
d.transport    = h.transport_type?.name        ?? undefined

// DESPUÉS:
d.housingId      = h.housing_type_id          ?? undefined
d.tenureId       = h.tenure_type_id           ?? undefined
d.constructionId = h.construction_material_id ?? undefined
d.commuteId      = h.commute_time_id          ?? undefined
d.transportId    = h.transport_type_id        ?? undefined
```

**Nota:** `h.housing_type_id` etc. son campos planos del `HousingProfileResource`. Verificar que efectivamente están presentes (el resource incluye `'housing_type_id' => $this->housing_type_id` — confirmado).

---

## Handlers de save: claves con sufijo _id

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

### saveFamily() (~línea 229):

```typescript
// ANTES:
guardian_marital_status: formData.repMarital   ?? null,
living_arrangement:      formData.living        ?? null,
household_head_type:     formData.householdHead ?? null,

// DESPUÉS:
guardian_marital_status_id: formData.repMaritalId    ?? null,
living_arrangement_id:      formData.livingId         ?? null,
household_head_type_id:     formData.householdHeadId  ?? null,
```

### saveSocioeconomic() (~línea 242):

```typescript
// ANTES:
income_range:         formData.incomeRange    ?? null,
income_source:        formData.incomeSource   ?? null,
remittance_country:   formData.remitFrom      ?? null,
employment_type:      formData.employmentType ?? null,
study_date:           formData.socioDate      ?? null,

// DESPUÉS:
income_range_id:         formData.incomeRangeId    ?? null,
income_source_id:        formData.incomeSourceId   ?? null,
remittance_country_id:   formData.remitFromId      ?? null,
employment_type_id:      formData.employmentTypeId ?? null,
study_date:              formData.socioDate        ?? null,  // el string YYYY-MM-DD ya es válido para 'date' en Laravel
```

### saveHousing() (~línea 280):

```typescript
// ANTES:
housing_type:          formData.housing       ?? null,
tenure_type:           formData.tenure        ?? null,
construction_material: formData.construction  ?? null,
commute_time:          formData.commute       ?? null,
transport_type:        formData.transport     ?? null,

// DESPUÉS:
housing_type_id:          formData.housingId      ?? null,
tenure_type_id:           formData.tenureId       ?? null,
construction_material_id: formData.constructionId ?? null,
commute_time_id:          formData.commuteId      ?? null,
transport_type_id:        formData.transportId    ?? null,
```

---

## Componentes Vue: UserFormS11Family.vue, UserFormS12Socioeconomic.vue, UserFormS14Housing.vue

Los tres componentes deben:
1. Recibir `catalogData` como prop (verificar si ya lo reciben; si no, agregarlo como los otros)
2. Reemplazar los selects con arrays locales por `v-for="item in catalogData.livingArrangements"` etc.
3. Hacer bind de `:value` con el ID correspondiente (`data.livingId`, `data.housingId`, etc.)
4. Usar `@change="setField('livingId', Number($event.target.value) || undefined)"` para actualizar el formData

---

## Impacto

| Archivo | Tipo de cambio |
|---------|---------------|
| `UserController.php` | +10 imports + 10 líneas en catalogData |
| `userEdit.ts` | +10 propiedades en UserFormCatalogData |
| `userForm.ts` | +12 campos *Id number |
| `useUserEditForm.ts` | 12 lecturas + 12 envíos cambiados + 1 normalización de fecha |
| `UserFormS11Family.vue` | 3 selects refactorizados |
| `UserFormS12Socioeconomic.vue` | 4 selects + 1 date field refactorizados |
| `UserFormS14Housing.vue` | 5 selects refactorizados |
| `UserEditUseCaseTest.php` | 3 nuevos tests (S11, S12, S14) |

Sin migraciones. Sin cambios en Resources ni FormRequests.
