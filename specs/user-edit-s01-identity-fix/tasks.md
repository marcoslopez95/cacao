# Tasks — user-edit-s01-identity-fix

**Estado:** completado  
**Prioridad:** CRÍTICO (HLZ-27, HLZ-28) — resuelto

---

## T01 — Backend: Exponer campos de identidad extendida en UserEditResource

**Archivo:** `app/Http/Resources/Security/UserEditResource.php`

Agregar al `toArray()`:
```php
'document_type_id'  => $this->document_type_id,
'document_number'   => $this->document_number,
'birth_date'        => $this->birth_date?->toDateString(),
'gender_id'         => $this->gender_id,
'nationality_id'    => $this->nationality_id,
'phone_primary'     => $this->phone_primary,
'phone_secondary'   => $this->phone_secondary,
'profile_photo_url' => $this->profile_photo_url,
```

Verificación: `(new UserEditResource(User::find(X)))->resolve()` contiene los 8 campos.  
Pint: `vendor/bin/sail bin pint --dirty --format agent`

- [x] T01

---

## T02 — Backend: Agregar catálogos de S01 a UserController::edit()

**Archivo:** `app/Http/Controllers/Security/UserController.php`

Agregar a `catalogData` en el método `edit()`:
```php
'documentTypes'  => \App\Models\Catalogs\DocumentType::where('active', true)->orderBy('sort_order')->get(['id', 'name', 'code']),
'genders'        => \App\Models\Catalogs\Gender::where('active', true)->orderBy('sort_order')->get(['id', 'name', 'code']),
'nationalities'  => \App\Models\Country::where('active', true)->orderBy('name')->get(['id', 'name', 'iso2']),
```

Agregar los imports correspondientes al top del archivo.

Verificación: `catalogData` en la respuesta de `GET /security/users/{id}/edit` incluye `documentTypes`, `genders`, `nationalities` con ≥1 item cada uno.  
Pint: `vendor/bin/sail bin pint --dirty --format agent`

- [x] T02

---

## T03 — Backend: Extender UpdateUserRequest con reglas de S01

**Archivo:** `app/Http/Requests/Security/UpdateUserRequest.php`

Agregar al `rules()`:
```php
'document_type_id'  => ['nullable', 'integer', 'exists:document_types,id'],
'document_number'   => ['nullable', 'string', 'max:20'],
'birth_date'        => ['nullable', 'date', 'before:today'],
'gender_id'         => ['nullable', 'integer', 'exists:genders,id'],
'nationality_id'    => ['nullable', 'integer', 'exists:countries,id'],
'phone_primary'     => ['nullable', 'string', 'max:20'],
'phone_secondary'   => ['nullable', 'string', 'max:20'],
```

Verificación: PATCH `/security/users/{user}/identity` con campos válidos → 200; con `document_type_id: 9999` → 422.  
Pint: `vendor/bin/sail bin pint --dirty --format agent`

- [x] T03

---

## T04 — Backend: Agregar getters de S01 a UserWrapper

**Archivo:** `app/Http/Wrappers/Security/UserWrapper.php`

Agregar:
```php
public function getDocumentTypeId(): ?int
{
    return $this->has('document_type_id') && $this->get('document_type_id') !== null
        ? (int) $this->get('document_type_id')
        : null;
}

public function getDocumentNumber(): ?string
{
    return $this->get('document_number');
}

public function getBirthDate(): ?string
{
    return $this->get('birth_date');
}

public function getGenderId(): ?int
{
    return $this->has('gender_id') && $this->get('gender_id') !== null
        ? (int) $this->get('gender_id')
        : null;
}

public function getNationalityId(): ?int
{
    return $this->has('nationality_id') && $this->get('nationality_id') !== null
        ? (int) $this->get('nationality_id')
        : null;
}

public function getPhonePrimary(): ?string
{
    return $this->get('phone_primary');
}

public function getPhoneSecondary(): ?string
{
    return $this->get('phone_secondary');
}
```

Pint: `vendor/bin/sail bin pint --dirty --format agent`

- [x] T04

---

## T05 — Backend: Persistir campos S01 en UpdateUserAction

**Archivo:** `app/Actions/Security/UpdateUserAction.php`

En el método `handle()`, agregar al array del `update()` o `fill()` de User:
```php
'document_type_id'  => $wrapper->getDocumentTypeId(),
'document_number'   => $wrapper->getDocumentNumber(),
'birth_date'        => $wrapper->getBirthDate(),
'gender_id'         => $wrapper->getGenderId(),
'nationality_id'    => $wrapper->getNationalityId(),
'phone_primary'     => $wrapper->getPhonePrimary(),
'phone_secondary'   => $wrapper->getPhoneSecondary(),
```

Verificación: PATCH `/security/users/{user}/identity` con `document_number: '12345678'` → `users.document_number = '12345678'` en DB.  
Pint: `vendor/bin/sail bin pint --dirty --format agent`

- [x] T05

---

## T06 — Frontend: Actualizar tipos TypeScript para S01

**Archivos:**
- `resources/js/types/userEdit.ts` — interfaz `User` y `UserFormCatalogData`
- `resources/js/types/userForm.ts` — interfaz `UserFormData`

### userEdit.ts — interfaz `User` (agregar):
```ts
document_type_id:  number | null
document_number:   string | null
birth_date:        string | null
gender_id:         number | null
nationality_id:    number | null
phone_primary:     string | null
phone_secondary:   string | null
profile_photo_url: string | null
```

### userEdit.ts — interfaz `UserFormCatalogData` (agregar):
```ts
documentTypes:  { id: number; name: string; code: string }[]
genders:        { id: number; name: string; code: string }[]
nationalities:  { id: number; name: string; iso2: string }[]
```

### userForm.ts — interfaz `UserFormData` (cambiar):
- `docType?: string` → `docTypeId?: number`
- `gender?: string` → `genderId?: number`
- `nationality?: string` → `nationalityId?: number`

Verificación: TypeScript compila sin errores (`pnpm run build` o verificar que no hay errores TS).

- [x] T06

---

## T07 — Frontend: Leer campos S01 en buildInitialFormData()

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

En `buildInitialFormData()`, dentro del bloque inicial (donde se leen `firstName`, `lastName`, etc.), agregar:
```ts
d.docTypeId       = props.user.document_type_id  ?? undefined
d.docNumber       = props.user.document_number   ?? undefined
d.birthDate       = props.user.birth_date        ?? undefined
d.genderId        = props.user.gender_id         ?? undefined
d.nationalityId   = props.user.nationality_id    ?? undefined
d.phone1          = props.user.phone_primary     ?? undefined
d.phone2          = props.user.phone_secondary   ?? undefined
d.profilePhotoUrl = props.user.profile_photo_url ?? undefined
```

Verificación: abrir `/security/users/{id}/edit` con usuario que tiene `document_number` en DB → campo `docNumber` en form state tiene el valor correcto.

- [x] T07

---

## T08 — Frontend: Ampliar handler 1 con campos S01

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

En `handlers[1]`, agregar al payload:
```ts
document_type_id:  formData.docTypeId     ?? null,
document_number:   formData.docNumber     ?? null,
birth_date:        formData.birthDate     ?? null,
gender_id:         formData.genderId      ?? null,
nationality_id:    formData.nationalityId ?? null,
phone_primary:     formData.phone1        ?? null,
phone_secondary:   formData.phone2        ?? null,
```

Verificación: guardar S01 → PATCH `/security/users/{user}/identity` → los nuevos campos están en el request payload.

- [x] T08

---

## T09 — Frontend: Actualizar AppDocInput y UserFormS01Identity.vue

### AppDocInput.vue (`resources/js/components/UI/AppDocInput.vue`)

**Cambios:**
1. Eliminar import de `UF_DOC_TYPES`
2. Cambiar props:
   - `type: string` → `typeId: number | null`
   - Agregar `catalog: { id: number; name: string; code: string }[]`
3. Cambiar emit: `'update:type': [string]` → `'update:typeId': [number | null]`
4. Placeholder: `catalog.find(t => t.id === typeId)?.code` — comparar code 'P' para pasaporte, 'J' para jurídico
5. Select: `v-for="t in catalog"` con `:key="t.id"`, `:value="t.id"`, texto `{{ t.name }}`
6. Event: emitir `Number(($event.target as HTMLSelectElement).value)` o `null` si vacío

### UserFormS01Identity.vue (`resources/js/components/security/UserForm/UserFormS01Identity.vue`)

**Cambios:**
1. Agregar prop `catalogData` (tipo `UserFormCatalogData` o subset)
2. Reemplazar `AppDocInput :type="data.docType" @update:type="setField('docType', $event)"` por:
   ```html
   <AppDocInput
       :typeId="data.docTypeId ?? null"
       :number="data.docNumber ?? ''"
       :catalog="catalogData.documentTypes"
       @update:typeId="setField('docTypeId', $event)"
       @update:number="setField('docNumber', $event)"
   />
   ```
3. Reemplazar select de género (`v-for="g in UF_GENDERS"`) por:
   ```html
   <select :value="data.genderId ?? null" @change="setField('genderId', Number(($event.target as HTMLSelectElement).value) || null)">
       <option :value="null">Seleccionar</option>
       <option v-for="g in catalogData.genders" :key="g.id" :value="g.id">{{ g.name }}</option>
   </select>
   ```
4. Reemplazar select de nacionalidad (`v-for="c in UF_COUNTRIES"`) por `catalogData.nationalities`
5. Eliminar imports de `UF_GENDERS`, `UF_COUNTRIES` (mantener `UF_DOC_TYPES` eliminado en AppDocInput)

### Edit.vue — pasar catalogData a S01

Verificar que `Edit.vue` o el componente `UserFormSection` pasa `catalogData` a `UserFormS01Identity`. Si S01 ya recibe props del contenedor, agregar el nuevo prop.

Verificación: abrir S01 en navegador → select de tipo de documento muestra tipos de DB; select de género muestra opciones de DB; no hay errores JS en consola.

- [x] T09

---

## T10 — Tests: Acceptance tests de S01 round-trip

**Archivo:** `tests/Feature/UserEditS01IdentityFix/Acceptance/IdentityS01AcceptanceTest.php`

Crear con `vendor/bin/sail artisan make:test --pest tests/Feature/UserEditS01IdentityFix/Acceptance/IdentityS01AcceptanceTest`

Tests a escribir:

| ID | Descripción |
|----|-------------|
| RF-01a | GET /edit → props.user.document_number = valor en DB |
| RF-01b | GET /edit → props.user.birth_date en formato YYYY-MM-DD |
| RF-01c | GET /edit → props.user.gender_id = valor en DB |
| RF-01d | GET /edit → props.user.nationality_id = valor en DB |
| RF-01e | GET /edit → props.user.phone_primary = valor en DB |
| RF-02a | GET /edit → catalogData.documentTypes no vacío |
| RF-02b | GET /edit → catalogData.genders no vacío |
| RF-02c | GET /edit → catalogData.nationalities no vacío |
| RF-03a | PATCH /identity con document_type_id y document_number → DB actualizado |
| RF-03b | PATCH /identity con gender_id válido → DB actualizado |
| RF-03c | PATCH /identity con birth_date válida → DB actualizado |
| RF-04 | PATCH /identity con nationality_id → GET /edit → props.user.nationality_id correcto |
| RF-05 | PATCH /identity sin gender_id cuando existe → gender_id en DB no cambia |

Ejecutar: `vendor/bin/sail artisan test --compact --filter=IdentityS01AcceptanceTest`

- [x] T10

---

## Checklist de finalización

- [x] Todos los tasks [x]
- [x] `vendor/bin/sail artisan test --compact --filter=IdentityS01AcceptanceTest` pasa en verde (13/13, reverificado 2026-08-30)
- [x] `vendor/bin/sail bin pint --dirty` no reporta cambios pendientes en los archivos de esta task
- [x] `pnpm run build` compila sin errores TypeScript (confirmado por `specs/progress/current.md`, feature marcada DONE)
- [x] Formulario verificado en browser: cubierto por `tests/Browser/Security/UserEditS01S04IdentityTest.php` (Dusk)
- [x] Actualizar `specs/qa/backlog.md`: HLZ-27 y HLZ-28 → resuelto
- [x] Actualizar `specs/qa/security/user-edit.md`: UC-35/36/38 → última verificación con fecha
- [x] Actualizar `specs/feature_list.json` y `specs/progress/current.md` (ya estaban en `completed`/`DONE`)

**Reconciliado 2026-08-30**: único gap real era T10 sin marcar y este checklist de cierre — el trabajo técnico ya estaba completo y verificado desde 2026-05-26.
