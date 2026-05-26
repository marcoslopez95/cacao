# Design — user-edit-s01-identity-fix

---

## Decisión de diseño: catálogos dinámicos (Opción A)

Los campos de catálogo de S01 (`document_type_id`, `gender_id`, `nationality_id`) usan el mismo patrón que S04/S05/S17: catálogos dinámicos cargados desde DB con IDs enteros. NO se usan strings estáticos. Razón: consistencia con el resto del formulario y con la integridad referencial del schema.

---

## Cambios backend

### UserEditResource
Agrega campos al `toArray()`:
```php
'document_type_id'   => $this->document_type_id,
'document_number'    => $this->document_number,
'birth_date'         => $this->birth_date?->toDateString(),   // YYYY-MM-DD
'gender_id'          => $this->gender_id,
'nationality_id'     => $this->nationality_id,
'phone_primary'      => $this->phone_primary,
'phone_secondary'    => $this->phone_secondary,
'profile_photo_url'  => $this->profile_photo_url,
```

### UserController::edit()
Agrega a `catalogData`:
```php
'documentTypes'  => DocumentType::where('active', true)->orderBy('sort_order')->get(['id','name','code']),
'genders'        => Gender::where('active', true)->orderBy('sort_order')->get(['id','name','code']),
'nationalities'  => Country::where('active', true)->orderBy('name')->get(['id','name','iso2']),
```

Imports: `App\Models\Catalogs\DocumentType`, `App\Models\Catalogs\Gender`, `App\Models\Country`

### UpdateUserRequest::rules()
Agrega:
```php
'document_type_id'  => ['nullable', 'integer', 'exists:document_types,id'],
'document_number'   => ['nullable', 'string', 'max:20'],
'birth_date'        => ['nullable', 'date', 'before:today'],
'gender_id'         => ['nullable', 'integer', 'exists:genders,id'],
'nationality_id'    => ['nullable', 'integer', 'exists:countries,id'],
'phone_primary'     => ['nullable', 'string', 'max:20'],
'phone_secondary'   => ['nullable', 'string', 'max:20'],
```

### UserWrapper
Agrega getters:
```php
public function getDocumentTypeId(): ?int   { return $this->has('document_type_id') ? ($this->get('document_type_id') !== null ? (int) $this->get('document_type_id') : null) : null; }
public function getDocumentNumber(): ?string { return $this->get('document_number'); }
public function getBirthDate(): ?string      { return $this->get('birth_date'); }
public function getGenderId(): ?int          { return $this->has('gender_id') ? ($this->get('gender_id') !== null ? (int) $this->get('gender_id') : null) : null; }
public function getNationalityId(): ?int     { return $this->has('nationality_id') ? ($this->get('nationality_id') !== null ? (int) $this->get('nationality_id') : null) : null; }
public function getPhonePrimary(): ?string   { return $this->get('phone_primary'); }
public function getPhoneSecondary(): ?string { return $this->get('phone_secondary'); }
```

Nota: usar `has()` antes de castear a evitar pasar `null` explícito cuando el campo no viene en el payload.

### UpdateUserAction
Agrega al `update()` de `users`:
```php
'document_type_id'  => $wrapper->getDocumentTypeId(),
'document_number'   => $wrapper->getDocumentNumber(),
'birth_date'        => $wrapper->getBirthDate(),
'gender_id'         => $wrapper->getGenderId(),
'nationality_id'    => $wrapper->getNationalityId(),
'phone_primary'     => $wrapper->getPhonePrimary(),
'phone_secondary'   => $wrapper->getPhoneSecondary(),
```

---

## Cambios frontend

### userEdit.ts — interfaz `User`
Agrega:
```ts
document_type_id:  number | null
document_number:   string | null
birth_date:        string | null   // YYYY-MM-DD
gender_id:         number | null
nationality_id:    number | null
phone_primary:     string | null
phone_secondary:   string | null
profile_photo_url: string | null
```

### userEdit.ts — interfaz `UserFormCatalogData`
Agrega:
```ts
documentTypes:  { id: number; name: string; code: string }[]
genders:        { id: number; name: string; code: string }[]
nationalities:  { id: number; name: string; iso2: string }[]
```

### userForm.ts — interfaz `UserFormData`
Cambia:
- `docType?: string` → `docTypeId?: number`
- `gender?: string` → `genderId?: number`
- `nationality?: string` → `nationalityId?: number`
- `docNumber`, `birthDate`, `phone1`, `phone1Dial`, `phone2`, `phone2Dial`, `profilePhotoUrl` → sin cambio de nombre

### buildInitialFormData() en useUserEditForm.ts
Agrega lecturas:
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

Nota: `phone1Dial` y `phone2Dial` no se cargan desde DB (no hay columna de dial) — el componente usa `'+58'` por defecto.

### Handler 1 en useUserEditForm.ts
Agrega al payload:
```ts
document_type_id:  formData.docTypeId     ?? null,
document_number:   formData.docNumber     ?? null,
birth_date:        formData.birthDate     ?? null,
gender_id:         formData.genderId      ?? null,
nationality_id:    formData.nationalityId ?? null,
phone_primary:     formData.phone1        ?? null,
phone_secondary:   formData.phone2        ?? null,
```

### AppDocInput.vue
**Props antes:** `type: string`, `number: string`  
**Props después:**
```ts
defineProps<{
    typeId: number | null
    number: string
    catalog: { id: number; name: string; code: string }[]
}>()
```

**Emit:** `'update:typeId': [number | null]` (antes `'update:type': [string]`)

**Placeholder:** usar `catalog.find(t => t.id === typeId)?.code` para el switch en vez de comparar string label.

**Select:**
```html
<option :value="null">Seleccionar</option>
<option v-for="t in catalog" :key="t.id" :value="t.id">{{ t.name }}</option>
```

### UserFormS01Identity.vue
- Reemplaza `AppDocInput :type / :number / @update:type` por `:typeId / :number / @update:typeId`
- Pasa `catalog="catalogData.documentTypes"` — recibe el prop `catalogData`
- Select de género: `v-for="g in catalogData.genders"` con `:value="g.id"`, bind a `data.genderId`
- Select de nacionalidad: `v-for="c in catalogData.nationalities"` con `:value="c.id"`, bind a `data.nationalityId`
- Recibe props `catalogData` (del `UserFormSection` o directamente desde `Edit.vue`)

---

## Arquitectura del prop catalogData en S01

`Edit.vue` ya pasa `catalogData` a los componentes que lo necesitan (S04, S05, S17). Revisar cómo S04 recibe `catalogData` y replicar el mismo patrón para S01.

---

## Tests de aceptación

Archivo: `tests/Feature/UserEditS01IdentityFix/Acceptance/IdentityS01AcceptanceTest.php`

| RF | Escenario | Tipo |
|----|-----------|------|
| RF-01 | GET /edit con user que tiene document_number, birth_date, gender_id, phone_primary → props correctos | Feature |
| RF-01 | GET /edit → catalogData incluye documentTypes (≥1), genders (≥1), nationalities (≥1) | Feature |
| RF-02 | PATCH /identity con todos los campos → DB actualizada | Feature |
| RF-04 | Round-trip: PATCH luego GET → mismos valores en props | Feature |
| RF-05 | PATCH /identity sin `gender_id` cuando ya existe uno → gender_id en DB no cambia | Feature |
