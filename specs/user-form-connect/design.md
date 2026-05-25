# Design — User Form Connect

**Feature:** `06-user-form-connect`

---

## Flujo general

```
Create.vue
  └─ Role picker → S01 + S02 + password_mode pills
  └─ "Crear usuario" → POST /security/users
                           ↓
                       store() redirige a security.users.edit
                           ↓
Edit.vue  (/security/users/{user}/edit)
  └─ Props: user, student|professor|guardian, todos los perfiles
  └─ S03–S17: cada "Guardar sección" → useHttp → endpoint propio
```

---

## Backend

### 1. Cambio en `UserController::store()`

```php
// Antes
return to_route('security.users.index');

// Después
return to_route('security.users.edit', $user);
```

### 2. Nuevo `UserController::edit()`

```php
public function edit(User $user): Response
{
    Gate::authorize('update', $user);

    $user->load(['roles']);
    $role = $user->roles->first()?->name;

    $props = [
        'user'              => new UserResource($user),
        'addresses'         => UserAddressResource::collection($user->addresses()->orderBy('primary', 'desc')->get()),
        'demographicProfile'=> $user->demographicProfile,
        'healthProfile'     => $user->healthProfile,
        'consents'          => $user->consents()->whereNull('revoked_at')->get(['id', 'type']),
        'documents'         => UserDocumentResource::collection($user->documents),
    ];

    // Role-specific
    if ($user->student) {
        $student = $user->student->load([
            'background', 'languages', 'familyProfile',
            'socioeconomicProfile', 'benefits', 'housingProfile',
        ]);
        $props['student'] = new StudentResource($student);
    }

    if ($user->professor) {
        $props['professor'] = new ProfessorResource($user->professor->load('staffProfile'));
    }

    if ($user->guardian) {
        $props['guardian'] = new GuardianResource($user->guardian->load('profile'));
    }

    return Inertia::render('security/Users/Edit', $props);
}
```

### 3. Nueva ruta

```php
Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
```

Agregar **antes** de la ruta `patch users/{user}` en el grupo `security`.

---

## Frontend

### `password_mode` en S02

`UserFormS02Credentials.vue` recibe `passwordMode` desde `formData`. Antes del campo de email, insertar:

```vue
<AppFormField label="Modo de contraseña" :col="12" required>
  <AppPillRadios
    :model-value="data.passwordMode ?? 'link'"
    :options="[
      { key: 'link',   label: 'Enviar link de activación' },
      { key: 'manual', label: 'Escribir contraseña' },
      { key: 'random', label: 'Generar aleatoria' },
    ]"
    @update:model-value="setField('passwordMode', $event)"
  />
</AppFormField>

<!-- Campo de contraseña solo si mode === 'manual' -->
<template v-if="data.passwordMode === 'manual'">
  ...AppPasswordInput...
</template>
<template v-else-if="data.passwordMode === 'random'">
  <p class="uf-help">El sistema generará una contraseña segura y la mostrará al crear el usuario.</p>
</template>
```

Agregar `passwordMode?: 'link' | 'manual' | 'random'` a `UserFormData`.

### `Create.vue` refactorizada

La vista actual muestra todas las secciones del rol. La nueva versión es una pantalla de dos pasos sin tabs:

```
┌─────────────────────────────────────────────┐
│  Paso 1 de 2 · Elegir rol                   │  ← step 0: role picker (sin cambios)
│  [Admin] [Estudiante] [Profesor] [Represent.]│
│                                             │
│           [Continuar]                       │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│  Nuevo usuario · Rol: Estudiante            │  ← step 1: solo S01 + S02
│                                             │
│  01 Identidad personal                      │
│  02 Credenciales + password_mode pills      │
│                                             │
│  [Cancelar]              [Crear usuario →]  │
└─────────────────────────────────────────────┘
```

- Sin `UserFormTabs`, sin `UserFormSideNav`, sin `UserFormHeader` completo
- Botón "Crear usuario" usa `useForm` de Inertia: `POST /security/users`
- Payload: `{ first_name, last_name, doc_type, doc_number, email, role, password_mode, password?, phone1, ... }`
- `StoreUserRequest` ya valida `first_name, last_name, email, role, password_mode, password` — extender para los campos de identidad extra

### `Edit.vue` (nueva)

Usa el mismo chrome de componentes que la Create.vue actual, pero en modo edición:

```
Header: "Completar perfil — Marcos López · Estudiante"
Tabs: según rol (los mismos UF_TABS)
Layout: SideNav + Sections
Footer: "Guardar borrador" (no aplica) | "Vista de usuario" (link al índice)
```

Cada `UserFormSection` recibe `saved-at` con la fecha real de la última persistencia (desde los props del backend), no el estado local.

### `useUserEditForm.ts` (nuevo composable)

```typescript
export function useUserEditForm(props: UserEditProps) {
    const saving = ref<number | null>(null)  // sección que se está guardando
    const errors = ref<Record<number, Record<string, string>>>({})

    async function saveSection(n: number): Promise<void> {
        saving.value = n
        errors.value[n] = {}
        try {
            await handlers[n]?.()
        } catch (e) {
            // errores mapeados a sección
        } finally {
            saving.value = null
        }
    }

    const handlers: Record<number, () => Promise<void>> = {
        3:  saveAddresses,
        4:  saveDemographic,
        5:  saveHealth,
        6:  saveConsents,
        7:  saveDocuments,
        8:  saveBackground,   // student
        9:  saveBackground,   // student (mismo endpoint)
        10: saveLanguages,    // student
        11: saveFamily,       // student
        12: saveSocioeconomic,// student
        13: saveBenefits,     // student
        14: saveHousing,      // student
        15: () => Promise.resolve(), // stub: S15 representantes
        16: saveGuardianProfile,
        17: saveStaffProfile,
    }
    ...
}
```

Cada función usa `useHttp` (Inertia v3) para disparar la petición al endpoint correspondiente.

---

## Mapping sección → endpoint

| Sec | Endpoint | Método | Notas |
|-----|----------|--------|-------|
| S03 | `/security/users/{user}/addresses` | POST/PUT/DELETE por item | Sync manual: crear nuevas, actualizar existentes, borrar eliminadas |
| S04 | `/security/users/{user}/demographic-profile` | PUT | Upsert |
| S05 | `/security/users/{user}/health-profile` | PUT | Upsert |
| S06 | `/security/users/{user}/consents` | POST (toggle por tipo) | Revoke: `PATCH .../consents/{consent}/revoke` |
| S07 | `/security/users/{user}/documents` | POST (metadata only) | Sin upload real en este arnés |
| S08+S09 | `/security/students/{student}/background` | PUT | Upsert, mismo endpoint |
| S10 | `/security/students/{student}/languages` | POST + DELETE | sync |
| S11 | `/security/students/{student}/family-profile` | PUT | Upsert |
| S12 | `/security/students/{student}/socioeconomic-profile` | PUT | Upsert |
| S13 | `/security/students/{student}/benefits/{benefit}` | POST + DELETE | sync |
| S14 | `/security/students/{student}/housing-profile` | PUT + PATCH .../services | dos calls |
| S15 | — | stub | Guardians linking es feature separado |
| S16 | `/security/guardians/{guardian}/profile` | PUT | Upsert |
| S17 | `/academic/professors/{professor}/staff-profile` | PUT | Upsert |

---

## Tipos TypeScript nuevos/modificados

```typescript
// Agregar a UserFormData
passwordMode?: 'link' | 'manual' | 'random'

// Props de Edit.vue (nuevo)
interface UserEditProps {
    user: UserResource
    addresses: UserAddressItem[]
    demographicProfile?: DemographicProfileData
    healthProfile?: HealthProfileData
    consents: Array<{ id: number; type: string }>  // consentimientos activos (sin revoked_at)
    documents: DocumentItem[]
    student?: StudentWithProfiles
    professor?: ProfessorWithStaff
    guardian?: GuardianWithProfile
}
```

---

## Invariantes y restricciones

- S03–S17 no se pueden guardar antes de que el usuario exista (sin user ID)
- S15 queda como stub — no hace llamadas al backend
- S07 guarda solo el campo `type` del documento — sin upload de archivo
- `UserController::edit()` requiere que el user tenga `first_name`, `last_name` (ya garantizado por `StoreUserRequest`)
- Pint obligatorio después de cada cambio PHP
