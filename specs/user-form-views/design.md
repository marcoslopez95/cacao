# Design — User Form Views

**Feature:** `05-user-form-views`
**Design source:** `cacao/project/CACAO Usuario - Crear.html` + `user-form.jsx` + `user-form-fields.jsx` + `user-form-sections.jsx` + `user-form-catalogs.jsx`

---

## Arquitectura

```
pages/security/Users/Create.vue               ← página fina (imports + template)
composables/forms/useUserForm.ts              ← ÚNICO dueño del estado reactivo
types/userForm.ts                             ← interfaces TypeScript de datos
types/userFormCatalogs.ts                     ← constantes de catálogos y metadata

components/security/UserForm/                 ← chrome del formulario
  UserFormRolePicker.vue
  UserFormHeader.vue
  UserFormTabs.vue
  UserFormSideNav.vue
  UserFormSection.vue
  UserFormFooter.vue

components/security/UserForm/                 ← renderers de secciones (1 por sección)
  UserFormS01Identity.vue
  UserFormS02Credentials.vue
  UserFormS03Address.vue
  UserFormS04Demographic.vue
  UserFormS05Health.vue
  UserFormS06Consents.vue
  UserFormS07Attachments.vue
  UserFormS08Academic.vue
  UserFormS09PrevEducation.vue
  UserFormS10Languages.vue
  UserFormS11Family.vue
  UserFormS12Socioeconomic.vue
  UserFormS13Benefits.vue
  UserFormS14Housing.vue
  UserFormS15Guardians.vue
  UserFormS16GuardianProfile.vue
  UserFormS17ProfessorProfile.vue

components/UI/ (nuevos primitivos)
  AppFormField.vue
  AppDocInput.vue
  AppTelInput.vue
  AppPasswordInput.vue
  AppPasswordStrength.vue
  AppToggle.vue
  AppToggleCard.vue
  AppPillRadios.vue
  AppRepeatable.vue
  AppFileZone.vue
  AppAvatarUpload.vue
```

---

## Backend mínimo

Solo lo necesario para que la ruta exista:

```php
// app/Http/Controllers/Security/UserController.php
public function create(): Response
{
    Gate::authorize('create', User::class);
    return Inertia::render('security/Users/Create');
}
```

```php
// routes/web.php — dentro del grupo security existente
Route::get('users/create', [UserController::class, 'create'])->name('security.users.create');
// IMPORTANTE: debe ir ANTES de Route::resource('users', ...) para no ser capturado como {user}
```

---

## Composable: `useUserForm.ts`

```typescript
type RoleKey = 'admin' | 'student' | 'professor' | 'guardian'
type SectionStatus = 'empty' | 'partial' | 'complete' | 'editing'
type AutosaveStatus = 'idle' | 'saving' | 'saved'

export function useUserForm() {
  // Estado
  const activeRole     = ref<RoleKey | ''>('')
  const activeTab      = ref<string>('')
  const activeSection  = ref<number | null>(null)   // scroll spy
  const formData       = reactive<UserFormData>({})
  const savedSections  = ref<Set<number>>(new Set())
  const editingSections = ref<Set<number>>(new Set())
  const autosave = ref<{ status: AutosaveStatus; when: Date | null }>({
    status: 'idle', when: null,
  })

  // Derivados
  const tabs         = computed(() => UF_TABS[activeRole.value] ?? [])
  const activeTabDef = computed(() => tabs.value.find(t => t.key === activeTab.value) ?? tabs.value[0])
  const completion   = computed(() => computeCompletion(formData, activeRole.value))

  // Acciones de rol / tab
  function pickRole(key: RoleKey): void
  function resetRole(): void

  // Acciones de sección
  function saveSection(n: number): void       // añade a savedSections, quita de editingSections
  function editSection(n: number): void       // añade a editingSections
  function cancelSection(n: number): void     // quita de editingSections
  function statusFor(n: number): SectionStatus

  // Scroll
  function scrollToSection(n: number): void
  function setupScrollSpy(sectionNums: number[]): () => void   // retorna cleanup

  // Autosave (simulado): watch formData → 'saving' → setTimeout 600ms → 'saved'

  return {
    activeRole, activeTab, activeSection, formData,
    savedSections, editingSections, autosave,
    tabs, activeTabDef, completion,
    pickRole, resetRole,
    saveSection, editSection, cancelSection, statusFor,
    scrollToSection, setupScrollSpy,
  }
}
```

---

## Tipos: `userForm.ts`

```typescript
export interface UserFormData {
  // S1 — Identidad
  firstName?: string; lastName?: string
  docType?: string; docNumber?: string
  birthDate?: string; gender?: string
  nationality?: string
  phone1?: string; phone1Dial?: string
  phone2?: string; phone2Dial?: string
  profilePhotoUrl?: string

  // S2 — Credenciales
  email?: string; password?: string

  // S3 — Dirección (repetible)
  addresses?: AddressItem[]

  // S4 — Demográfico
  birthCity?: string; birthState?: string; birthCountry?: string
  indigenous?: boolean; indigenousComm?: string; nativeLang?: string
  returnedMigrant?: boolean; returnFrom?: string
  religion?: string; sport?: boolean; sportName?: string
  culture?: string

  // S5 — Salud
  bloodType?: string; weight?: string; height?: string
  disability?: boolean; disabilityType?: string; disabilityDesc?: string
  specialNeeds?: boolean; specialNeedsDesc?: string
  chronic?: string; medication?: string; allergies?: string
  insurance?: boolean; insuranceType?: string
  emergencyName?: string; emergencyPhone?: string; emergencyDial?: string; emergencyRel?: string

  // S6 — Consentimientos
  consent_data?: boolean; consent_image?: boolean
  consent_whatsapp?: boolean; consent_email?: boolean

  // S7 — Documentos (repetible)
  attachments?: AttachmentItem[]

  // S8 — Perfil académico (student)
  studentCode?: string; academicStatus?: string; enrollDate?: string
  modality?: string; shift?: string; admission?: string
  gpa?: string; grade?: string

  // S9 — Antecedentes educativos (student)
  prevInstitution?: string; prevInstitutionType?: string; gradYear?: string
  prevGpa?: string; transferReason?: string; digitalLevel?: string
  repeated?: boolean; repeatedDesc?: string
  priorUni?: boolean; priorUniDesc?: string
  motherEdu?: string; fatherEdu?: string

  // S10 — Idiomas (repetible, student)
  languages?: LanguageItem[]

  // S11 — Familia (student)
  repMarital?: string; repChildren?: string; siblings?: string; siblingPos?: string
  living?: string; householdHead?: string; householdHeadName?: string

  // S12 — Socioeconómico (student)
  incomeRange?: string; incomeSource?: string; contributors?: string; socioDate?: string
  remit?: boolean; remitFrom?: string
  studentWorks?: boolean; employmentType?: string; weekHours?: string
  externalScholarship?: boolean; scholarshipName?: string
  instBenefits?: boolean

  // S13 — Beneficios (repetible, student)
  benefits?: BenefitItem[]

  // S14 — Vivienda (student)
  housing?: string; tenure?: string; construction?: string
  rooms?: string; bathrooms?: string; peopleHome?: string
  commute?: string; transport?: string
  svc_water?: boolean; svc_elec?: boolean; svc_gas?: boolean; svc_inet?: boolean
  otherServices?: string

  // S15 — Representantes (repetible, student)
  guardians?: GuardianItem[]

  // S16 — Perfil representante (guardian)
  occupation?: string; employer?: string; workPhone?: string; workDial?: string
  guardianMarital?: string; guardianEdu?: string

  // S17 — Perfil profesor (professor)
  empCode?: string; degree?: string; specialty?: string
  contract?: string; dedication?: string; weeklyHours?: string
  hireDate?: string; endDate?: string; emplStatus?: string
  isCoord?: boolean; coordDept?: string; coordSince?: string
}

export interface AddressItem {
  __id: number; country?: string; state?: string
  muni?: string; parish?: string; zone?: string
  line1?: string; line2?: string; primary?: boolean
}
export interface LanguageItem {
  __id: number; lang?: string; level?: string; mother?: boolean
}
export interface AttachmentItem {
  __id: number; type?: string; filename?: string; verified?: boolean
}
export interface BenefitItem {
  __id: number; benefit?: string; active?: boolean; start?: string; end?: string
}
export interface GuardianItem {
  __id: number; search?: string; kinship?: string; main?: boolean; emergency?: boolean
}
```

---

## Catálogos: `userFormCatalogs.ts`

Port directo de `user-form-catalogs.jsx` a TypeScript. Exporta:
- `UF_DOC_TYPES`, `UF_GENDERS`, `UF_COUNTRIES`, `UF_STATES_VE`, `UF_MUNICIPIOS_DTTO`, `UF_PARROQUIAS`, `UF_ZONES`
- `UF_LANGUAGES`, `UF_RELIGIONS`, `UF_BLOOD`, `UF_DISABILITY_TYPES`, `UF_INSURANCE`, `UF_ATTACH_TYPES`
- `UF_ACADEMIC_STATUSES`, `UF_STUDY_MODALITIES`, `UF_SHIFTS`, `UF_ADMISSION`, `UF_GRADES`
- `UF_INSTITUTION_TYPES`, `UF_TRANSFER_REASONS`, `UF_DIGITAL_LEVELS`, `UF_EDU_LEVELS`, `UF_LANG_LEVELS`
- `UF_MARITAL`, `UF_LIVING`, `UF_HOUSEHOLD_HEAD`
- `UF_INCOME_RANGES`, `UF_INCOME_SOURCES`, `UF_EMPLOYMENT_TYPES`
- `UF_BENEFITS`, `UF_HOUSING`, `UF_TENURE`, `UF_CONSTRUCTION`, `UF_COMMUTE`, `UF_TRANSPORT`
- `UF_KINSHIP`, `UF_CONTRACT`, `UF_DEDICATION`, `UF_EMPL_STATUS`, `UF_DEPARTMENTS`
- `UF_ROLES: Record<RoleKey, RoleMeta>` (key, label, description, icon)
- `UF_TABS: Record<RoleKey, TabDef[]>` (key, label, sections: number[])
- `UF_SECTIONS: Record<number, SectionMeta>` (title, sub, roles, repeat?, comfy?, note?)

---

## API de componentes chrome

### `UserFormRolePicker`
```typescript
// Props: ninguna
// Emits: pick(role: RoleKey)
```
- 4 cards con `RoleKey`; click selecciona, dblclick o botón "Continuar" emite `pick`
- Botón "Cancelar" navega a `index().url()` (Wayfinder)
- Muestra sección count desde `ROLE_SECTION_COUNT`

### `UserFormHeader`
```typescript
// Props: role, completion (%), autosave
// Emits: changeRole()
```
- Título "Nuevo usuario" + tag modo
- Pill del rol con botón "cambiar" → emite `changeRole`
- Barra de progreso + porcentaje + estado autosave

### `UserFormTabs`
```typescript
// Props: tabs: TabDef[], activeTab: string, completion
// Emits: update:activeTab(key: string)
```
- Tab completo → check visual. Tab parcial → punto de advertencia

### `UserFormSideNav`
```typescript
// Props: sections: number[], activeSection: number | null, completion, savedSections, editingSections
// Emits: scrollTo(secNum: number)
```
- Un link por sección con dot de estado (CSS classes: empty / partial / complete / editing)

### `UserFormSection`
```typescript
// Props: num, title, sub, roleTag?, comfy?, status, saved?
// Emits: save(), enterEdit(), cancel()
// Slot: default (campo renderer)
```
- `id="sec-{num}"` para scroll spy
- Footer con botón "Guardar sección" (no se muestra si `readonly`)

### `UserFormFooter`
```typescript
// Props: completion, autosave
// Emits: discard(), saveDraft(), submit()
```
- Botón "Crear usuario" disabled si `completion.pct < 50`

---

## API de primitivos UI

### `AppFormField`
```typescript
{ label?: string, required?: boolean, optional?: boolean, adminOnly?: boolean,
  help?: string, error?: string, ok?: string, col?: 1|2|3|4|5|6|7|8|9|10|11|12 }
// Aplica clase CSS `col-{col}` en el wrapper div
```

### `AppDocInput`
```typescript
{ type: string, number: string }
// Emits: update:type, update:number
// Select de UF_DOC_TYPES + input numérico (strip non-digits)
```

### `AppTelInput`
```typescript
{ modelValue: string, dialCode: string }
// Emits: update:modelValue, update:dialCode
```

### `AppPasswordInput`
```typescript
{ modelValue: string }
// Emits: update:modelValue
// Toggle show/hide con ícono
```

### `AppPasswordStrength`
```typescript
{ value: string }
// 5 barras de color: danger → warning → success según score
```

### `AppToggle`
```typescript
{ modelValue: boolean, label?: string, sub?: string, disabled?: boolean }
// Emits: update:modelValue
```

### `AppToggleCard`
```typescript
{ modelValue: boolean, label: string, sub?: string }
// Emits: update:modelValue
// Card con fondo que cambia al estar checked
```

### `AppPillRadios`
```typescript
{ modelValue: string, options: string[] | { key: string, label: string }[] }
// Emits: update:modelValue
```

### `AppRepeatable`
```typescript
{ items: any[], titlePrefix?: string, emptyTitle?: string, emptySub?: string, addLabel?: string }
// Emits: add(), remove(index: number)
// Slot: item(item, index)
// Pill "Principal" si item.__main === true
```

### `AppFileZone`
```typescript
{ filename?: string, accept?: string }
// Emits: update:filename
// Simula upload con filename demo en prototipo
```

### `AppAvatarUpload`
```typescript
{ initials?: string, hasImage?: boolean }
// Emits: upload(), remove()
```

---

## Completion algorithm (port de user-form.jsx)

```typescript
function computeCompletion(data: UserFormData, role: RoleKey | '') {
  const checks: Record<number, string[] | (() => boolean)> = {
    1: ['firstName','lastName','docNumber','birthDate','nationality','phone1'],
    2: ['email'],
    3: () => (data.addresses?.length ?? 0) > 0 && !!data.addresses?.[0]?.country && !!data.addresses?.[0]?.line1,
    4: ['birthCity','birthCountry'],
    5: ['emergencyName','emergencyPhone','emergencyRel','bloodType'],
    6: () => !!data.consent_data,
    7: () => (data.attachments?.length ?? 0) > 0,
    8: ['studentCode','academicStatus','enrollDate'],
    9: ['prevInstitution','prevInstitutionType','gradYear'],
    10: () => (data.languages?.length ?? 0) > 0,
    11: ['repMarital','living'],
    12: ['incomeRange','incomeSource'],
    13: () => (data.benefits?.length ?? 0) > 0,
    14: ['housing','tenure','rooms','peopleHome'],
    15: () => (data.guardians?.length ?? 0) > 0,
    16: ['occupation','employer'],
    17: ['empCode','degree','hireDate'],
  }
  // Itera solo las secciones del rol activo
  // Retorna { pct, sectionsComplete: Set<number>, sectionsPartial: Set<number> }
}
```

---

## Tabs por rol

```
admin:     Identidad [1,2,3,4] · Salud [5] · Documentos [7,6]
student:   Identidad [1,2,3,4] · Salud [5] · Académico [8,9,10] · Familia [11,15] · Socioeconómico [12,13,14] · Documentos [7,6]
professor: Identidad [1,2,3,4] · Salud [5] · Profesional [17] · Documentos [7,6]
guardian:  Identidad [1,2,3,4] · Salud [5] · Representante [16] · Documentos [7,6]
```
