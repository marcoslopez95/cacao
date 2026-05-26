# Design — user-edit-health-fix

**Feature ID:** `user-edit-health-fix`

---

## Patrón aplicado

Igual que S04/S09/S16/S17 en `user-edit-catalog-ids`:
1. Backend inyecta catálogos como `{id, name, code}` en `catalogData`
2. `buildInitialFormData()` lee el ID plano del resource (ya presente en `HealthProfileResource`)
3. `saveHealth()` envía las claves con sufijo `_id` y valor numérico
4. Componente Vue usa `v-for` sobre `catalogData.*` con `:value="item.id"`

**La diferencia con las features anteriores:** el Resource ya está correcto (expone `blood_type_id`, `disability_type_id`, `insurance_type_id` como campos planos). Solo hay que leerlos en el frontend.

---

## Cambio 1 — UserController::edit(): agregar catálogos a catalogData

**Archivo:** `app/Http/Controllers/Security/UserController.php`

Agregar tres imports al inicio:
```php
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\InsuranceType;
```

En el bloque `$props['catalogData']`, agregar al final del array:
```php
'bloodTypes'      => BloodType::active()->ordered()->get(['id', 'name', 'code']),
'disabilityTypes' => DisabilityType::active()->ordered()->get(['id', 'name', 'code']),
'insuranceTypes'  => InsuranceType::active()->ordered()->get(['id', 'name', 'code']),
```

---

## Cambio 2 — UserFormCatalogData: agregar las tres propiedades

**Archivo:** `resources/js/types/userEdit.ts`

En la interface `UserFormCatalogData`, agregar:
```typescript
bloodTypes:       Array<{ id: number; name: string; code: string }>
disabilityTypes:  Array<{ id: number; name: string; code: string }>
insuranceTypes:   Array<{ id: number; name: string; code: string }>
```

---

## Cambio 3 — UserFormData: agregar campos *Id, mantener strings para compatibilidad

**Archivo:** `resources/js/types/userForm.ts`

Agregar los nuevos campos ID:
```typescript
bloodTypeId?:      number
disabilityTypeId?: number
insuranceTypeId?:  number
```

Los campos string `bloodType?`, `disabilityType?`, `insuranceType?` pueden permanecer como `@deprecated` o eliminarse si no hay otros usos fuera de S05. Verificar antes de eliminar.

---

## Cambio 4 — buildInitialFormData(): leer IDs planos

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

Bloque `if (props.healthProfile)` (~línea 462):

```typescript
// ANTES (buggy — lee objetos anidados ausentes):
d.bloodType      = h.blood_type?.name        ?? undefined
d.disabilityType = h.disability_type?.name   ?? undefined
d.insuranceType  = h.insurance_type?.name    ?? undefined

// DESPUÉS (correcto — lee IDs planos presentes en el resource):
d.bloodTypeId      = h.blood_type_id      ?? undefined
d.disabilityTypeId = h.disability_type_id ?? undefined
d.insuranceTypeId  = h.insurance_type_id  ?? undefined
```

Las líneas para campos de texto libre (`disability_description`, `chronic_condition`, etc.) no cambian.

---

## Cambio 5 — saveHealth(): enviar IDs con sufijo _id

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

Función `saveHealth()` (~línea 168):

```typescript
// ANTES (buggy — claves sin _id, strings):
blood_type:       formData.bloodType        ?? null,
disability_type:  formData.disabilityType   ?? null,
insurance_type:   formData.insuranceType    ?? null,

// DESPUÉS (correcto — claves con _id, IDs numéricos):
blood_type_id:       formData.bloodTypeId      ?? null,
disability_type_id:  formData.disabilityTypeId ?? null,
insurance_type_id:   formData.insuranceTypeId  ?? null,
```

---

## Cambio 6 — UserFormS05Health.vue: selects con catalogData

**Archivo:** `resources/js/components/security/UserForm/UserFormS05Health.vue`

Los tres selects de catálogo deben:
- Recibir `catalogData` como prop (igual que S04, S09, S13, S16, S17 ya lo hacen)
- Usar `v-for="item in catalogData.bloodTypes"` con `:value="item.id"` y `{{ item.name }}` para el texto
- Hacer bind con `@change="setField('bloodTypeId', Number($event.target.value) || undefined)"`

**Nota sobre el select de disability_type:** Solo es relevante cuando `has_disability = true`. El comportamiento condicional no cambia, solo el origen de los datos.

---

## Impacto

| Archivo | Tipo de cambio |
|---------|---------------|
| `UserController.php` | +3 imports + 3 líneas en catalogData |
| `userEdit.ts` | +3 propiedades en UserFormCatalogData |
| `userForm.ts` | +3 campos *Id number |
| `useUserEditForm.ts` | 3 lecturas + 3 envíos cambiados |
| `UserFormS05Health.vue` | 3 selects refactorizados |
| `UserEditUseCaseTest.php` | nuevo test S05 |

Sin migraciones. Sin cambios en Resources ni FormRequests.
