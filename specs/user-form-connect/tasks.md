# Tasks — User Form Connect

**Feature:** `06-user-form-connect`
**Scope:** Full-stack — guardado progresivo sección a sección
**Depends on:** `user-form-views` (UI ya construida)

---

## Overall Progress

- [x] Task 01 — Backend: ruta `edit` + `UserController::edit()` + cambio de redirect en `store()`
- [x] Task 02 — Tipos: `passwordMode` en `UserFormData` + interfaces `UserEditProps`
- [x] Task 03 — S02: pills de `password_mode` (link / manual / random)
- [x] Task 04 — `Create.vue` refactorizada: solo S01 + S02 + `useForm` de Inertia
- [x] Task 05 — `Edit.vue` + `useUserEditForm.ts` (skeleton: props, formData inicial, dispatcher)
- [x] Task 06 — Wire S03 (dirección) — sync de addresses
- [x] Task 07 — Wire S04 + S05 (demográfico + salud)
- [x] Task 08 — Wire S06 + S07 (consentimientos + documentos metadata)
- [x] Task 09 — Wire S08+S09 (background estudiantil)
- [x] Task 10 — Wire S10 (idiomas)
- [x] Task 11 — Wire S11+S12 (familia + socioeconómico)
- [x] Task 12 — Wire S13+S14 (beneficios + vivienda)
- [x] Task 13 — Wire S16 + S17 (perfil representante + perfil personal)
- [x] Task 14 — Tests Pest: `edit()` props, redirect de `store()`, validación `password_mode`
- [x] Task 15 — Polish: loading states, errores inline por sección, toast feedback

---

## Task Detail

---

### Task 01 — Backend: ruta `edit` + `UserController::edit()` + redirect

**Files:**
- `routes/web.php`
- `app/Http/Controllers/Security/UserController.php`

**Steps:**

1. En `routes/web.php`, dentro del grupo `security`, agregar **después** de la ruta `create` y **antes** de `patch users/{user}`:
```php
Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
```

2. En `UserController::store()`, cambiar la última línea:
```php
// Antes
return to_route('security.users.index');

// Después
return to_route('security.users.edit', $user);
```
Nota: `$user` ya está disponible — `$action->handle($wrapper)` retorna el `User` recién creado. Capturarlo: `$user = $action->handle($wrapper)`.

3. Agregar método `edit()` a `UserController`:
```php
public function edit(User $user): Response
{
    Gate::authorize('update', $user);

    $user->load('roles');

    $props = [
        'user'               => new UserResource($user),
        'addresses'          => UserAddressResource::collection(
                                    $user->addresses()->orderByDesc('primary')->get()
                                ),
        'demographicProfile' => $user->demographicProfile,
        'healthProfile'      => $user->healthProfile,
        'consents'           => $user->consents()->whereNull('revoked_at')->get(['id', 'type']),
        'documents'          => UserDocumentResource::collection($user->documents),
    ];

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

4. Agregar los `use` necesarios en el controlador (UserAddressResource, UserDocumentResource, StudentResource, ProfessorResource, GuardianResource — verificar cuáles ya existen con `find app/Http/Resources -name "*.php"`).

5. `vendor/bin/sail artisan route:list --name=security.users` para verificar la nueva ruta.
6. `vendor/bin/sail artisan wayfinder:generate`
7. `vendor/bin/sail bin pint --dirty --format agent`

**Done criteria:**
- `GET /security/users/{user}/edit` existe y requiere auth
- `POST /security/users` redirige a la nueva vista edit (no al index)
- Wayfinder genera función `edit(user)`
- Pint clean

---

### Task 02 — Tipos: `passwordMode` + `UserEditProps`

**Files:**
- `resources/js/types/userForm.ts`
- `resources/js/types/userEdit.ts` (nuevo)

**En `userForm.ts`**, agregar en S2:
```typescript
// S2 — Credenciales de acceso
email?: string
password?: string
passwordMode?: 'link' | 'manual' | 'random'  // ← agregar
```

**Nuevo `userEdit.ts`**:
```typescript
import type { RoleKey } from './userFormCatalogs'

export interface UserResource {
    id: number
    first_name: string
    last_name: string
    name: string
    email: string
    role: RoleKey
    active: boolean
}

export interface UserAddressItem {
    id: number
    country?: string
    state?: string
    municipality?: string
    parish?: string
    zone?: string
    line1?: string
    line2?: string
    primary: boolean
}

export interface UserDocumentItem {
    id: number
    type: string
    filename?: string
    verified: boolean
    verified_by?: string | null
    verified_at?: string | null
}

export interface DemographicProfileData {
    birth_city?: string
    birth_state?: string
    birth_country?: string
    indigenous?: boolean
    indigenous_community?: string
    native_language?: string
    returned_migrant?: boolean
    return_from?: string
    religion?: string
    sport?: boolean
    sport_name?: string
    culture?: string
}

export interface HealthProfileData {
    blood_type?: string
    weight?: number | null
    height?: number | null
    disability?: boolean
    disability_type?: string
    disability_description?: string
    special_needs?: boolean
    special_needs_description?: string
    chronic_condition?: string
    medication?: string
    allergies?: string
    has_insurance?: boolean
    insurance_type?: string
    emergency_name?: string
    emergency_phone?: string
    emergency_phone_country_code?: string
    emergency_relationship?: string
}

export interface StudentLanguageItem {
    id: number
    language: string
    level: string
    is_mother_tongue: boolean
}

export interface StudentBenefitItem {
    id: number
    benefit: string
    active: boolean
    starts_at?: string | null
    ends_at?: string | null
}

// Props completas de Edit.vue
export interface UserEditProps {
    user: UserResource
    addresses: UserAddressItem[]
    demographicProfile?: DemographicProfileData
    healthProfile?: HealthProfileData
    consents: Array<{ id: number; type: string }>
    documents: UserDocumentItem[]
    student?: {
        id: number
        student_code?: string
        academic_status?: string
        enroll_date?: string
        modality?: string
        shift?: string
        admission?: string
        gpa?: number | null
        grade?: string
        background?: Record<string, unknown> | null
        languages?: StudentLanguageItem[]
        familyProfile?: Record<string, unknown> | null
        socioeconomicProfile?: Record<string, unknown> | null
        benefits?: StudentBenefitItem[]
        housingProfile?: Record<string, unknown> | null
    }
    professor?: {
        id: number
        staffProfile?: Record<string, unknown> | null
    }
    guardian?: {
        id: number
        profile?: Record<string, unknown> | null
    }
}
```

**Done criteria:**
- Sin errores TypeScript: `vendor/bin/sail npm run build`
- `UserEditProps` tiene todos los campos que `UserController::edit()` va a devolver

---

### Task 03 — S02: pills de `password_mode`

**Files:**
- `resources/js/components/security/UserForm/UserFormS02Credentials.vue`

**Cambios:**
1. Antes del campo email, agregar el selector de modo:
```vue
<AppFormField label="Modo de contraseña" :col="12" required>
  <AppPillRadios
    :model-value="data.passwordMode ?? 'link'"
    :options="[
      { key: 'link',   label: 'Enviar link de activación' },
      { key: 'manual', label: 'Escribir contraseña manualmente' },
      { key: 'random', label: 'Generar contraseña aleatoria' },
    ]"
    @update:model-value="setField('passwordMode', $event)"
  />
</AppFormField>
```

2. Campo de contraseña condicionado:
```vue
<template v-if="(data.passwordMode ?? 'link') === 'manual'">
  <AppFormField label="Contraseña" :col="6" required>
    <AppPasswordInput :model-value="data.password ?? ''" @update:model-value="setField('password', $event)" />
  </AppFormField>
  <AppPasswordStrength :value="data.password ?? ''" />
</template>
<template v-else-if="(data.passwordMode ?? 'link') === 'random'">
  <p class="uf-help col-12">
    Se generará una contraseña aleatoria segura. El sistema la mostrará al finalizar la creación.
  </p>
</template>
```

3. Inicializar `passwordMode` en `'link'` si no está definido al montar S02.

**Done criteria:**
- Pills visibles en la sección S02
- Modo `link`: sin campo de contraseña
- Modo `manual`: muestra password input + strength meter
- Modo `random`: muestra texto informativo

---

### Task 04 — `Create.vue` refactorizada

**Files:**
- `resources/js/pages/security/Users/Create.vue`

**La nueva Create.vue muestra solo S01 + S02.** Reemplazar el contenido actual por:

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { store } from '@/actions/security/users'
import { index } from '@/routes/security/users'
import UserFormRolePicker from '@/components/security/UserForm/UserFormRolePicker.vue'
import UserFormS01Identity from '@/components/security/UserForm/UserFormS01Identity.vue'
import UserFormS02Credentials from '@/components/security/UserForm/UserFormS02Credentials.vue'
import UserFormSection from '@/components/security/UserForm/UserFormSection.vue'
import type { RoleKey } from '@/types/userFormCatalogs'
import { UF_ROLES } from '@/types/userFormCatalogs'
import { ref, reactive } from 'vue'
import type { UserFormData } from '@/types/userForm'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index().url },
            { title: 'Nuevo usuario' },
        ],
    },
})

const activeRole = ref<RoleKey | ''>('')
const formData   = reactive<UserFormData>({})

function setField<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
    ;(formData as Record<string, unknown>)[key as string] = value
}

const form = useForm(() => ({
    first_name:    formData.firstName ?? '',
    last_name:     formData.lastName ?? '',
    email:         formData.email ?? '',
    role:          activeRole.value,
    password_mode: formData.passwordMode ?? 'link',
    password:      formData.password ?? undefined,
    // Campos extra de identidad opcionales
    doc_type:  formData.docType,
    doc_number: formData.docNumber,
    phone1:    formData.phone1,
    phone1_country_code: formData.phone1Dial,
    gender:    formData.gender,
    nationality: formData.nationality,
    birth_date: formData.birthDate,
}))

const canSubmit = computed(() =>
    !!formData.firstName && !!formData.lastName && !!formData.email && !!activeRole.value,
)

function submit(): void {
    form.post(store().url)
}
</script>

<template>
  <Head title="Nuevo usuario" />

  <div class="up-content">
    <UserFormRolePicker v-if="!activeRole" @pick="k => { activeRole = k }" />

    <template v-else>
      <div class="uf-create-header">
        <h1>Nuevo usuario
          <span class="uf-mode-tag">{{ UF_ROLES[activeRole].label }}</span>
        </h1>
        <p class="uf-create-sub">
          Completá los datos básicos. Podrás agregar el perfil completo en el siguiente paso.
        </p>
        <button class="uf-change-role" @click="activeRole = ''">Cambiar rol</button>
      </div>

      <div class="uf-sections uf-sections--create">
        <UserFormSection
          num="01" title="Identidad personal" sub="Datos básicos del usuario."
          status="empty" :section-id="1"
          @save="() => {}" @enter-edit="() => {}" @cancel="() => {}"
        >
          <UserFormS01Identity :data="formData" :set-field="setField" />
        </UserFormSection>

        <UserFormSection
          num="02" title="Credenciales de acceso" sub="Correo y contraseña de inicio de sesión."
          status="empty" :section-id="2"
          @save="() => {}" @enter-edit="() => {}" @cancel="() => {}"
        >
          <UserFormS02Credentials :data="formData" :set-field="setField" />
        </UserFormSection>
      </div>

      <!-- Mensajes de error del servidor -->
      <div v-if="Object.keys(form.errors).length" class="uf-server-errors">
        <p v-for="(msg, field) in form.errors" :key="field" class="uf-error">{{ msg }}</p>
      </div>

      <div class="uf-create-footer">
        <button class="btn-ghost" @click="router.visit(index().url)">Cancelar</button>
        <button
          class="btn-primary"
          :disabled="!canSubmit || form.processing"
          @click="submit"
        >
          {{ form.processing ? 'Creando usuario…' : 'Crear usuario →' }}
        </button>
      </div>
    </template>
  </div>
</template>
```

**Done criteria:**
- Visitar `/security/users/create` → role picker
- Elegir rol → muestra S01 + S02 (sin tabs ni sidebar)
- Llenar datos → botón "Crear usuario" habilitado
- Submit → POST a `/security/users` → redirect a Edit.vue con el nuevo user ID
- Errores de validación aparecen debajo de las secciones

---

### Task 05 — `Edit.vue` + `useUserEditForm.ts` (skeleton)

**Files:**
- `resources/js/pages/security/Users/Edit.vue` (nuevo)
- `resources/js/composables/forms/useUserEditForm.ts` (nuevo)

**`Edit.vue`** — estructura completa con tabs, side nav y secciones. Recibe `UserEditProps` como props de Inertia. Cada sección se inicializa con los datos del backend. El composable maneja el guardado.

```vue
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { onUnmounted, watch } from 'vue'
import type { UserEditProps } from '@/types/userEdit'
import { useUserEditForm } from '@/composables/forms/useUserEditForm'
import { index } from '@/routes/security/users'
// Importar todos los componentes de chrome + secciones S01-S17

const props = defineProps<UserEditProps>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index().url },
            { title: props.user.name },
        ],
    },
})

const {
    activeTab, activeSection,
    formData, savedSections, editingSections,
    tabs, activeTabDef, completion,
    activeRole,
    saving,
    saveSection, editSection, cancelSection, statusFor,
    scrollToSection, setupScrollSpy, setField,
    UF_SECTIONS,
} = useUserEditForm(props)

let cleanupSpy: (() => void) | null = null
watch(activeTabDef, () => {
    cleanupSpy?.()
    if (!activeTabDef.value) return
    setTimeout(() => { cleanupSpy = setupScrollSpy(activeTabDef.value!.sections) }, 50)
}, { immediate: true })
onUnmounted(() => cleanupSpy?.())
</script>
```

**`useUserEditForm.ts`** — extiende la lógica de `useUserFormPage.ts` con:
1. Inicializa `formData` desde los props del backend (mapeando snake_case → camelCase)
2. `saving = ref<number | null>(null)` para tracking del spinner por sección
3. `saveSection(n)` → despacha al handler de esa sección → actualiza `savedSections`
4. Handlers vacíos por ahora (stub `() => Promise.resolve()`), se implementan en tasks 06–13

**Mapping de props backend → formData al inicializar:**
```typescript
// S01
formData.firstName = props.user.first_name
formData.lastName  = props.user.last_name
// S03
formData.addresses = props.addresses.map(a => ({ __id: a.id, ...a }))
// S04
if (props.demographicProfile) {
    formData.birthCity    = props.demographicProfile.birth_city
    formData.birthCountry = props.demographicProfile.birth_country
    // ...etc
}
// ...y así para cada sección
```

**Done criteria:**
- Visitar `/security/users/{user}/edit` renderiza la vista con tabs y secciones
- `formData` tiene los datos del backend pre-cargados
- Botón "Guardar sección" no rompe nada (handlers son stubs)
- Sin errores TypeScript

---

### Task 06 — Wire S03: Dirección (sync de addresses)

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

El endpoint de addresses es CRUD por item (no batch). El handler de S03 necesita sincronizar:
- Items nuevos (sin ID real → `__id` es `Date.now()`) → `POST /security/users/{user}/addresses`
- Items existentes (con ID real → guardar separado en `savedAddressIds`) → `PUT /security/users/{user}/addresses/{id}`
- Items eliminados (estaban guardados y ya no están) → `DELETE /security/users/{user}/addresses/{id}`

```typescript
// Al nivel raíz del composable (no dentro de funciones async):
// const http = useHttp()   ← declarar aquí, reutilizar en todos los handlers

async function saveAddresses(): Promise<void> {
    const userId = props.user.id
    const current = formData.addresses ?? []

    // Separar nuevos vs existentes vs eliminados
    const savedIds = new Set(props.addresses.map(a => a.id))
    const currentIds = new Set(current.filter(a => savedIds.has(a.__id)).map(a => a.__id))

    // POST nuevos
    for (const addr of current.filter(a => !savedIds.has(a.__id))) {
        await http.post(store_address(userId).url, addrPayload(addr))
    }
    // PUT existentes
    for (const addr of current.filter(a => savedIds.has(a.__id))) {
        await http.put(update_address(userId, addr.__id).url, addrPayload(addr))
    }
    // DELETE eliminados
    for (const id of [...savedIds].filter(id => !currentIds.has(id))) {
        await http.delete(destroy_address(userId, id).url)
    }
}
```

**Done criteria:**
- Guardar S03 con 2 direcciones → 2 registros en BD
- Guardar S03 con una dirección eliminada → registro borrado en BD
- Recargar la página → direcciones pre-cargadas correctamente

---

### Task 07 — Wire S04 + S05: Demográfico + Salud

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

Ambos usan `PUT` upsert. Implementar `saveDemographic()` y `saveHealth()` usando `useHttp`:

```typescript
async function saveDemographic(): Promise<void> {
    const userId = props.user.id
    await http.put(upsert_demographic_profile(userId).url, {
        birth_city:            formData.birthCity,
        birth_state:           formData.birthState,
        birth_country:         formData.birthCountry,
        indigenous:            formData.indigenous ?? false,
        indigenous_community:  formData.indigenousComm,
        native_language:       formData.nativeLang,
        returned_migrant:      formData.returnedMigrant ?? false,
        return_from:           formData.returnFrom,
        religion:              formData.religion,
        sport:                 formData.sport ?? false,
        sport_name:            formData.sportName,
        culture:               formData.culture,
    })
}

async function saveHealth(): Promise<void> {
    const userId = props.user.id
    await http.put(upsert_health_profile(userId).url, {
        blood_type:                 formData.bloodType,
        weight:                     formData.weight ? Number(formData.weight) : null,
        height:                     formData.height ? Number(formData.height) : null,
        disability:                 formData.disability ?? false,
        disability_type:            formData.disabilityType,
        disability_description:     formData.disabilityDesc,
        special_needs:              formData.specialNeeds ?? false,
        special_needs_description:  formData.specialNeedsDesc,
        chronic_condition:          formData.chronic,
        medication:                 formData.medication,
        allergies:                  formData.allergies,
        has_insurance:              formData.insurance ?? false,
        insurance_type:             formData.insuranceType,
        emergency_name:             formData.emergencyName,
        emergency_phone:            formData.emergencyPhone,
        emergency_phone_country_code: formData.emergencyDial,
        emergency_relationship:     formData.emergencyRel,
    })
}
```

**Done criteria:**
- Guardar S04 → registro en `demographic_profiles` o actualizado
- Guardar S05 → registro en `health_profiles` o actualizado
- Recargar → datos pre-cargados

---

### Task 08 — Wire S06 + S07: Consentimientos + Documentos

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

**S06 — Consentimientos:**
Los consentimientos son toggle por tipo. Al guardar S06:
- Si el tipo está activo en `formData` pero NO en `props.consents` → `POST /users/{user}/consents` con el tipo
- Si el tipo NO está activo pero SÍ estaba en `props.consents` → `PATCH /users/{user}/consents/{consent}/revoke`

```typescript
async function saveConsents(): Promise<void> {
    const userId = props.user.id
    const types = ['data', 'image', 'whatsapp', 'email'] as const
    const activeNow = new Set(
        types.filter(t => formData[`consent_${t}` as keyof UserFormData])
    )
    const wasSaved = new Set(props.consents)

    for (const t of types) {
        if (activeNow.has(t) && !wasSaved.has(t)) {
            await http.post(store_consent(userId).url, { type: t })
        }
        if (!activeNow.has(t) && wasSaved.has(t)) {
            // Necesita el ID del consent — props debe incluir { type, id }[]
            // Ajustar UserEditProps.consents a ConsentItem[] en lugar de string[]
            const consentId = props.consents.find(c => c.type === t)?.id
            if (consentId) {
                await http.patch(revoke_consent(userId, consentId).url)
            }
        }
    }
}
```

Nota: ajustar el tipo de `consents` en `UserEditProps` a `Array<{ id: number; type: string }>`.

**S07 — Documentos (metadata only, sin upload real):**
Stub que guarda solo `type` de cada documento nuevo. Los uploads de archivos reales son trabajo futuro.

**Done criteria:**
- Marcar `consent_data` y guardar S06 → registro en `user_consents`
- Desmarcar y guardar S06 → consent revocado

---

### Task 09 — Wire S08+S09: Background estudiantil

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

```typescript
async function saveBackground(): Promise<void> {
    if (!props.student) return
    await http.put(upsert_background(props.student.id).url, {
        // S08
        student_code:      formData.studentCode,
        academic_status:   formData.academicStatus,
        enroll_date:       formData.enrollDate,
        modality:          formData.modality,
        shift:             formData.shift,
        admission:         formData.admission,
        gpa:               formData.gpa ? Number(formData.gpa) : null,
        grade:             formData.grade,
        // S09
        prev_institution:      formData.prevInstitution,
        prev_institution_type: formData.prevInstitutionType,
        grad_year:             formData.gradYear ? Number(formData.gradYear) : null,
        prev_gpa:              formData.prevGpa ? Number(formData.prevGpa) : null,
        transfer_reason:       formData.transferReason,
        digital_level:         formData.digitalLevel,
        repeated:              formData.repeated ?? false,
        repeated_description:  formData.repeatedDesc,
        prior_university:      formData.priorUni ?? false,
        prior_university_description: formData.priorUniDesc,
        mother_education:      formData.motherEdu,
        father_education:      formData.fatherEdu,
    })
}
```

S08 y S09 usan el mismo handler — tanto "Guardar S08" como "Guardar S09" llaman a `saveBackground()`.

**Done criteria:**
- Guardar S08 o S09 → registro en `student_backgrounds` creado/actualizado
- Recargar → datos pre-cargados

---

### Task 10 — Wire S10: Idiomas

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

Idiomas son CRUD por item (como addresses). Sync:
- Nuevos → `POST /students/{student}/languages`
- Eliminados → `DELETE /students/{student}/languages/{language}`
- Existentes (nivel cambiado) → no hay `PUT` individual — borrar y recrear si cambia

```typescript
async function saveLanguages(): Promise<void> {
    if (!props.student) return
    const studentId = props.student.id
    const savedIds   = new Set((props.student.languages ?? []).map(l => l.id))
    const current    = formData.languages ?? []
    const currentIds = new Set(current.filter(l => savedIds.has(l.__id)).map(l => l.__id))

    // DELETE eliminados
    for (const id of [...savedIds].filter(id => !currentIds.has(id))) {
        await http.delete(destroy_language(studentId, id).url)
    }
    // POST nuevos
    for (const lang of current.filter(l => !savedIds.has(l.__id))) {
        await http.post(store_language(studentId).url, {
            language:         lang.lang,
            level:            lang.level,
            is_mother_tongue: lang.mother ?? false,
        })
    }
}
```

**Done criteria:**
- Agregar idioma y guardar → registro en `student_languages`
- Eliminar idioma y guardar → registro borrado

---

### Task 11 — Wire S11+S12: Familia + Socioeconómico

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

Ambos son upsert vía PUT. Implementar `saveFamily()` y `saveSocioeconomic()`.

**S11 payload** → `StudentFamilyProfile`: `marital_status`, `number_of_children`, `number_of_siblings`, `sibling_position`, `living_arrangement`, `household_head_type`, `household_head_name`

**S12 payload** → `StudentSocioeconomicProfile`: `income_range`, `income_source`, `contributing_members`, `study_date`, `receives_remittances`, `remittance_origin`, `student_works`, `employment_type`, `weekly_work_hours`, `has_external_scholarship`, `scholarship_name`, `receives_institutional_benefits`

**Done criteria:**
- Guardar S11 → registro en `student_family_profiles`
- Guardar S12 → registro en `student_socioeconomic_profiles`

---

### Task 12 — Wire S13+S14: Beneficios + Vivienda

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

**S13 — Beneficios (sync, como languages):**
```typescript
// POST /students/{student}/benefits/{benefit} (donde benefit es el nombre del beneficio codificado)
// DELETE /students/{student}/benefits/{benefit}
```
El endpoint usa el nombre del beneficio como param. Sync similar a idiomas.

**S14 — Vivienda (dos calls):**
```typescript
async function saveHousing(): Promise<void> {
    if (!props.student) return
    const studentId = props.student.id
    // 1. Upsert el perfil de vivienda
    await http.put(upsert_housing_profile(studentId).url, {
        housing_type:      formData.housing,
        tenure:            formData.tenure,
        construction_type: formData.construction,
        rooms:             Number(formData.rooms),
        bathrooms:         Number(formData.bathrooms),
        people_count:      Number(formData.peopleHome),
        commute_time:      formData.commute,
        transport:         formData.transport,
    })
    // 2. Sync servicios básicos
    const services: string[] = []
    if (formData.svc_water) services.push('water')
    if (formData.svc_elec)  services.push('electricity')
    if (formData.svc_gas)   services.push('gas')
    if (formData.svc_inet)  services.push('internet')
    await http.patch(sync_housing_services(studentId).url, { services })
}
```

**Done criteria:**
- Guardar S13 → beneficios sincronizados
- Guardar S14 → `housing_profiles` y servicios actualizados

---

### Task 13 — Wire S16 + S17: Perfil representante + Perfil personal

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`

**S16 (guardian):**
```typescript
async function saveGuardianProfile(): Promise<void> {
    if (!props.guardian) return
    await http.put(upsert_guardian_profile(props.guardian.id).url, {
        occupation:       formData.occupation,
        employer:         formData.employer,
        work_phone:       formData.workPhone,
        work_phone_country_code: formData.workDial,
        marital_status:   formData.guardianMarital,
        education_level:  formData.guardianEdu,
    })
}
```

**S17 (professor):**
```typescript
async function saveStaffProfile(): Promise<void> {
    if (!props.professor) return
    await http.put(upsert_staff_profile(props.professor.id).url, {
        employee_code:     formData.empCode,
        degree:            formData.degree,
        specialty:         formData.specialty,
        contract_type:     formData.contract,
        dedication:        formData.dedication,
        weekly_hours:      formData.weeklyHours ? Number(formData.weeklyHours) : null,
        hire_date:         formData.hireDate,
        end_date:          formData.endDate,
        employment_status: formData.emplStatus,
        is_coordinator:    formData.isCoord ?? false,
        coordinator_department: formData.coordDept,
        coordinator_since: formData.coordSince,
    })
}
```

**Done criteria:**
- Guardar S16 (usuario guardian) → registro en `guardian_profiles`
- Guardar S17 (usuario profesor) → registro en `staff_profiles`

---

### Task 14 — Tests Pest

**Files:**
- `tests/Feature/Security/UserControllerEditTest.php` (nuevo)

**Tests a escribir:**

```php
// 1. edit() retorna 200 y la vista correcta
test('admin puede ver la vista de editar usuario', function () { ... })

// 2. edit() carga los props correctos (user, addresses, demographicProfile...)
test('edit props include user and related profiles', function () { ... })

// 3. store() ahora redirige a security.users.edit (no a index)
test('crear usuario redirige a edit', function () {
    $response = $this->actingAs($admin)->post(route('security.users.store'), [...]);
    $response->assertRedirectToRoute('security.users.edit', $user);
})

// 4. password_mode validation
test('store requiere password_mode válido', function () { ... })
test('store con password_mode manual requiere password', function () { ... })
test('store con password_mode link no requiere password', function () { ... })
```

**Done criteria:**
- `vendor/bin/sail artisan test --compact --filter=UserControllerEdit` pasa
- Pint clean

---

### Task 15 — Polish: loading states, errores inline, toast

**Files:**
- `resources/js/composables/forms/useUserEditForm.ts`
- `resources/js/components/security/UserForm/UserFormSection.vue`
- `resources/js/pages/security/Users/Edit.vue`

**Loading state por sección:**
- `saving` ref indica qué sección está en proceso
- `UserFormSection` recibe `:is-saving="saving === sectionId"` → muestra spinner en el botón

**Errores inline:**
- Si `saveSection` falla con errores de validación (422), mapear los errores al `errors` ref
- `UserFormSection` recibe `:errors="errors[sectionId]"` y los pasa a los fields

**Toast en éxito:**
- Inertia v3: usar `router.reload({ only: [] })` o `Inertia::flash()` para mostrar toast
- O: gestionar el toast en el composable al resolver la promesa

**Done criteria:**
- Hacer clic en "Guardar sección" → botón muestra spinner mientras guarda
- Error de validación → campo con error se resalta (clase `has-error`)
- Éxito → badge "Guardado" aparece en la cabecera de la sección
