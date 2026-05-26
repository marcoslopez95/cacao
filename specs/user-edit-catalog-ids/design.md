# Design — user-edit-catalog-ids

**Feature ID:** `user-edit-catalog-ids`

---

## Patrón a seguir: igual que S10/S13/S03

El patrón ya establecido en el proyecto:

1. **Backend (`UserController::edit()`):** inyectar el catálogo como colección de `{id, name}` en `props.catalogData`
2. **Tipo TS (`UserFormCatalogData`):** agregar la nueva propiedad al interface
3. **Tipo TS (`UserFormData`):** cambiar el campo de `string?` a `number?` (ID)
4. **Componente Vue:** recibir `catalogData` como prop, usar `v-for` sobre la colección con `:value="item.id"`, bind del ID
5. **Composable (`useUserEditForm.ts`):**
   - En `buildInitialFormData()`: leer el ID del campo resource (p.ej. `p.religion_id`)
   - En el handler de guardado: enviar el campo con sufijo `_id` y valor numérico

---

## Catálogos a agregar por sección

### S04 — Perfil demográfico

| Campo form nuevo | Campo BD | Modelo Eloquent | Tabla |
|---|---|---|---|
| `birthStateId` (number) | `birth_state_id` | `State` | `states` |
| `birthCountryId` (number) | `birth_country_id` | `Country` | `countries` |
| `nativeLangId` (number) | `native_language_id` | `Language` | `languages` |
| `religionId` (number) | `religion_id` | `Religion` | `religions` |
| `previousCountryId` (number) | `previous_country_id` | `Country` | `countries` |

Catálogos a agregar a `catalogData`:
- `religions`: `Religion::active()->ordered()->get(['id', 'name'])`
- `countries` y `languages` ya están en `catalogData`

**Nota:** `birthState` y `birthCountry` se manejan como selects filtrados igual que en S03 (países ya en `catalogData`, estados ya en `catalogData` filtrados por `country_id`). Solo falta el componente S04 que use IDs en lugar de strings.

### S09 — Antecedentes educativos

| Campo form nuevo | Campo BD | Modelo Eloquent | Tabla |
|---|---|---|---|
| `prevInstitutionTypeId` (number) | `institution_type_id` | `InstitutionType` | `institution_types` |
| `transferReasonId` (number) | `transfer_reason_id` | `TransferReason` | `transfer_reasons` |
| `digitalLevelId` (number) | `digital_level_id` | `DigitalLevel` | `digital_levels` |
| `motherEduId` (number) | `mother_education_level_id` | `EducationLevel` | `education_levels` |
| `fatherEduId` (number) | `father_education_level_id` | `EducationLevel` | `education_levels` |

Catálogos nuevos en `catalogData`:
- `institutionTypes`: `InstitutionType::active()->ordered()->get(['id', 'name'])`
- `transferReasons`: `TransferReason::active()->ordered()->get(['id', 'name'])`
- `digitalLevels`: `DigitalLevel::active()->ordered()->get(['id', 'name'])`
- `educationLevels`: `EducationLevel::active()->ordered()->get(['id', 'name'])` (compartido con S16)

### S16 — Perfil representante

| Campo form nuevo | Campo BD | Modelo Eloquent | Tabla |
|---|---|---|---|
| `guardianMaritalId` (number) | `marital_status_id` | `MaritalStatus` | `marital_statuses` |
| `guardianEduId` (number) | `education_level_id` | `EducationLevel` | `education_levels` |

Catálogos nuevos:
- `maritalStatuses`: `MaritalStatus::active()->ordered()->get(['id', 'name'])`
- `educationLevels`: ya agregado para S09 (reutilizar)

### S17 — Perfil personal (profesor)

| Campo form nuevo | Campo BD | Modelo Eloquent | Tabla |
|---|---|---|---|
| `contractTypeId` (number) | `contract_type_id` | `ContractType` | `contract_types` |
| `dedicationTypeId` (number) | `dedication_type_id` | `DedicationType` | `dedication_types` |
| `emplStatusId` (number) | `employment_status_id` | `EmploymentStatus` | `employment_statuses` |
| `coordDeptId` (number) | `coordinated_department_id` | `Department` | `departments` |

Catálogos nuevos:
- `contractTypes`: `ContractType::active()->ordered()->get(['id', 'name'])`
- `dedicationTypes`: `DedicationType::active()->ordered()->get(['id', 'name'])`
- `employmentStatuses`: `EmploymentStatus::active()->ordered()->get(['id', 'name'])`
- `departments`: `Department::orderBy('name')->get(['id', 'name'])` (sin `active` scope — verificar si el modelo lo tiene)

---

## Campos a deprecar en `UserFormData`

Los campos string que se reemplazan por ID:
- `birthState?: string` → `birthStateId?: number`
- `birthCountry?: string` → `birthCountryId?: number`
- `nativeLang?: string` → `nativeLangId?: number`
- `returnFrom?: string` → `previousCountryId?: number` (usando countries)
- `religion?: string` → `religionId?: number`
- `prevInstitutionType?: string` → `prevInstitutionTypeId?: number`
- `transferReason?: string` → `transferReasonId?: number`
- `digitalLevel?: string` → `digitalLevelId?: number`
- `motherEdu?: string` → `motherEduId?: number`
- `fatherEdu?: string` → `fatherEduId?: number`
- `guardianMarital?: string` → `guardianMaritalId?: number`
- `guardianEdu?: string` → `guardianEduId?: number`
- `contract?: string` → `contractTypeId?: number`
- `dedication?: string` → `dedicationTypeId?: number`
- `emplStatus?: string` → `emplStatusId?: number`
- `coordDept?: string` → `coordDeptId?: number`

**Estrategia:** agregar los campos `*Id` y mantener los string temporalmente como `@deprecated` para no romper otros usos potenciales. Los componentes dejan de usar los string y usan los ID.

---

## Cambios en `buildInitialFormData()`

Leer el `_id` directamente del resource en lugar de `.name`:

```typescript
// Antes (incorrecto):
d.religion = p.religion?.name ?? undefined

// Después (correcto):
d.religionId = p.religion_id ?? undefined
```

---

## Cambios en los handlers de guardado

```typescript
// saveDemographic() — antes (incorrecto):
birth_state:    formData.birthState   ?? null,  // string
birth_country:  formData.birthCountry ?? null,  // string
native_language: formData.nativeLang  ?? null,  // string
religion:       formData.religion     ?? null,  // string

// Después (correcto):
birth_state_id:     formData.birthStateId      ?? null,
birth_country_id:   formData.birthCountryId    ?? null,
native_language_id: formData.nativeLangId      ?? null,
religion_id:        formData.religionId        ?? null,
previous_country_id: formData.previousCountryId ?? null,
```

Idem para `saveBackground()`, `saveGuardianProfile()`, `saveStaffProfile()`.

---

## Impacto

- Sin migraciones (los campos `_id` ya existen en las tablas)
- Backend: un cambio en `UserController::edit()` para agregar catálogos
- Frontend: 4 componentes + 2 tipos + 1 composable
- Tests: agregar assertions de que los IDs persisten en DB
