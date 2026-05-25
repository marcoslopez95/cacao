# Tasks — User Form Views

**Feature:** `05-user-form-views`
**Scope:** Frontend only — Vue UI sin conexión a backend
**Depends on:** ninguno

---

## Overall Progress

- [x] Task 01 — Route + controller stub (`UserController::create()`)
- [x] Task 02 — Tipos TypeScript: `userFormCatalogs.ts` + `userForm.ts`
- [x] Task 03 — Composable: `useUserForm.ts`
- [x] Task 04 — UI primitivos batch A: `AppFormField`, `AppToggle`, `AppToggleCard`, `AppPillRadios`
- [x] Task 05 — UI primitivos batch B: `AppDocInput`, `AppTelInput`, `AppPasswordInput`, `AppPasswordStrength`
- [x] Task 06 — UI primitivos batch C: `AppRepeatable`, `AppFileZone`, `AppAvatarUpload`
- [x] Task 07 — Chrome A: `UserFormRolePicker` + `UserFormHeader`
- [x] Task 08 — Chrome B: `UserFormTabs` + `UserFormSideNav`
- [x] Task 09 — Chrome C: `UserFormSection` + `UserFormFooter`
- [x] Task 10 — Página: `Create.vue` (esqueleto: role picker → layout → tabs → secciones → footer)
- [x] Task 11 — Secciones S01 Identidad + S02 Credenciales
- [x] Task 12 — Sección S03 Dirección (repetible)
- [x] Task 13 — Sección S04 Perfil demográfico
- [x] Task 14 — Sección S05 Salud
- [x] Task 15 — Secciones S06 Consentimientos + S07 Documentos adjuntos
- [x] Task 16 — Secciones S08 Perfil académico + S09 Antecedentes educativos
- [x] Task 17 — Sección S10 Idiomas (repetible)
- [x] Task 18 — Secciones S11 Perfil familiar + S12 Perfil socioeconómico
- [x] Task 19 — Secciones S13 Beneficios (repetible) + S14 Vivienda
- [x] Task 20 — Sección S15 Representantes (repetible)
- [x] Task 21 — Secciones S16 Perfil representante + S17 Perfil del personal
- [ ] Task 22 — Polish: verificación visual completa en los 4 roles

---

## Nota: patrón de mutación en secciones

Todas las secciones S01–S17 usan este contrato uniforme:

```typescript
// Props
defineProps<{ data: UserFormData }>()
// No emiten — mutan el reactive directamente a través del helper del composable

// En Create.vue, se pasa el helper del composable:
// <UserFormS01Identity :data="formData" :set-field="setField" />

// En useUserForm.ts, agregar:
function setField<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
  ;(formData as Record<string, unknown>)[key as string] = value
}
// Retornarlo en el objeto de retorno del composable

// En cada sección:
// props.data.firstName  → para leer
// props.setField('firstName', value)  → para escribir
```

Para arrays (addresses, languages, etc.):
```typescript
function setArray<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
  ;(formData as Record<string, unknown>)[key as string] = value
}
// Uso: props.setField('addresses', [...items, newItem])
```

Agregar `setField` a los props de cada sección y al tipo de retorno del composable.

---

## Task Detail

---

### Task 01 — Route + Controller stub

**Files:**
- `app/Http/Controllers/Security/UserController.php`
- `routes/web.php`

**Steps:**

1. En `UserController`, agregar método `create()`:
```php
public function create(): Response
{
    Gate::authorize('create', User::class);
    return Inertia::render('security/Users/Create');
}
```

2. En `routes/web.php`, dentro del grupo `security` donde están las rutas de usuarios, agregar **antes** del `Route::resource('users', ...)` o del `Route::get('users/{user}', ...)`:
```php
Route::get('users/create', [UserController::class, 'create'])->name('security.users.create');
```
El orden importa: debe estar ANTES de cualquier ruta con `{user}` para que `create` no se interprete como un ID de usuario.

3. Ejecutar `vendor/bin/sail artisan route:list --name=security.users` y verificar que aparece la ruta `security.users.create`.

4. Ejecutar `vendor/bin/sail artisan wayfinder:generate` para que Wayfinder genere la función `create()`.

5. Ejecutar `vendor/bin/sail bin pint --dirty --format agent`.

**Done criteria:**
- `vendor/bin/sail artisan route:list` muestra `GET /security/users/create` con nombre `security.users.create`
- Wayfinder genera `@/actions/security/users.ts` con función `create()`
- Visitar `/security/users/create` renderiza la página (aunque esté vacía)
- Pint clean

---

### Task 02 — Tipos TypeScript

**Files:**
- `resources/js/types/userFormCatalogs.ts` (nuevo)
- `resources/js/types/userForm.ts` (nuevo)

**`userFormCatalogs.ts`:**

Port directo de `cacao/project/user-form-catalogs.jsx`. Exportar como constantes TypeScript:
- Todos los arrays: `UF_DOC_TYPES`, `UF_GENDERS`, `UF_COUNTRIES` (con `key`, `label`, `dial`), `UF_STATES_VE`, `UF_MUNICIPIOS_DTTO`, `UF_PARROQUIAS`, `UF_ZONES`, `UF_LANGUAGES`, `UF_RELIGIONS`, `UF_BLOOD`, `UF_DISABILITY_TYPES`, `UF_INSURANCE`, `UF_ATTACH_TYPES`, `UF_ACADEMIC_STATUSES`, `UF_STUDY_MODALITIES`, `UF_SHIFTS`, `UF_ADMISSION`, `UF_GRADES`, `UF_INSTITUTION_TYPES`, `UF_TRANSFER_REASONS`, `UF_DIGITAL_LEVELS`, `UF_EDU_LEVELS`, `UF_LANG_LEVELS`, `UF_MARITAL`, `UF_LIVING`, `UF_HOUSEHOLD_HEAD`, `UF_INCOME_RANGES`, `UF_INCOME_SOURCES`, `UF_EMPLOYMENT_TYPES`, `UF_BENEFITS`, `UF_HOUSING`, `UF_TENURE`, `UF_CONSTRUCTION`, `UF_COMMUTE`, `UF_TRANSPORT`, `UF_KINSHIP`, `UF_CONTRACT`, `UF_DEDICATION`, `UF_EMPL_STATUS`, `UF_DEPARTMENTS`
- Interfaces: `RoleMeta { key, label, description, icon }`, `TabDef { key, label, sections: number[] }`, `SectionMeta { title, sub, roles: string[], repeat?: boolean, comfy?: boolean, note?: string }`
- `export type RoleKey = 'admin' | 'student' | 'professor' | 'guardian'`
- `export const UF_ROLES: Record<RoleKey, RoleMeta>`
- `export const UF_TABS: Record<RoleKey, TabDef[]>` — con los tabs del design.md
- `export const UF_SECTIONS: Record<number, SectionMeta>` — las 17 secciones del design.md
- `export const ROLE_SECTION_COUNT: Record<RoleKey, number>` — `{ admin: 7, student: 15, professor: 8, guardian: 8 }`

**`userForm.ts`:**

Exportar todas las interfaces de `design.md` — ver sección "Tipos: userForm.ts":
`UserFormData`, `AddressItem`, `LanguageItem`, `AttachmentItem`, `BenefitItem`, `GuardianItem`

**Done criteria:**
- Sin errores TypeScript: `vendor/bin/sail npm run build` pasa sin errores de tipo en estos archivos
- `UF_TABS.student` tiene 6 tabs con las secciones correctas

---

### Task 03 — Composable `useUserForm.ts`

**Files:**
- `resources/js/composables/forms/useUserForm.ts` (nuevo)

**Implementar:**

```typescript
import { ref, reactive, computed, watch } from 'vue'
import type { RoleKey } from '@/types/userFormCatalogs'
import { UF_TABS } from '@/types/userFormCatalogs'
import type { UserFormData } from '@/types/userForm'

type SectionStatus = 'empty' | 'partial' | 'complete' | 'editing'

export function useUserForm() {
  const activeRole      = ref<RoleKey | ''>('')
  const activeTab       = ref<string>('')
  const activeSection   = ref<number | null>(null)
  const formData        = reactive<UserFormData>({})
  const savedSections   = ref<Set<number>>(new Set())
  const editingSections = ref<Set<number>>(new Set())
  const autosave = ref<{ status: 'idle' | 'saving' | 'saved'; when: Date | null }>({
    status: 'idle', when: null,
  })
  let autosaveTimer: ReturnType<typeof setTimeout> | null = null

  const tabs = computed(() => UF_TABS[activeRole.value as RoleKey] ?? [])
  const activeTabDef = computed(
    () => tabs.value.find(t => t.key === activeTab.value) ?? tabs.value[0],
  )
  const completion = computed(() => computeCompletion(formData, activeRole.value as RoleKey))

  function pickRole(key: RoleKey): void {
    activeRole.value = key
    activeTab.value = UF_TABS[key]?.[0]?.key ?? ''
    savedSections.value = new Set()
    editingSections.value = new Set()
  }
  function resetRole(): void {
    activeRole.value = ''
    activeTab.value = ''
  }

  function saveSection(n: number): void {
    savedSections.value = new Set([...savedSections.value, n])
    const next = new Set(editingSections.value)
    next.delete(n)
    editingSections.value = next
  }
  function editSection(n: number): void {
    editingSections.value = new Set([...editingSections.value, n])
  }
  function cancelSection(n: number): void {
    const next = new Set(editingSections.value)
    next.delete(n)
    editingSections.value = next
  }
  function statusFor(n: number): SectionStatus {
    if (editingSections.value.has(n)) return 'editing'
    if (completion.value.sectionsComplete.has(n)) return 'complete'
    if (completion.value.sectionsPartial.has(n)) return 'partial'
    return 'empty'
  }

  function scrollToSection(n: number): void {
    document.getElementById(`sec-${n}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
  function setupScrollSpy(sectionNums: number[]): () => void {
    const els = sectionNums.map(n => document.getElementById(`sec-${n}`)).filter(Boolean) as HTMLElement[]
    if (!els.length) return () => {}
    const obs = new IntersectionObserver(entries => {
      const visible = entries.filter(e => e.isIntersecting).sort((a, b) => a.target.getBoundingClientRect().top - b.target.getBoundingClientRect().top)
      if (visible.length) activeSection.value = parseInt(visible[0].target.id.replace('sec-', ''))
    }, { rootMargin: '-30% 0px -50% 0px' })
    els.forEach(el => obs.observe(el))
    return () => obs.disconnect()
  }

  // Autosave simulado
  watch(formData, () => {
    if (autosaveTimer) clearTimeout(autosaveTimer)
    autosave.value = { status: 'saving', when: null }
    autosaveTimer = setTimeout(() => {
      autosave.value = { status: 'saved', when: new Date() }
    }, 600)
  }, { deep: true })

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

**`computeCompletion`** — implementar en el mismo archivo (ver sección "Completion algorithm" en design.md). Retorna `{ pct: number, sectionsComplete: Set<number>, sectionsPartial: Set<number> }`.

**Done criteria:**
- `pickRole('student')` → `tabs.value` tiene 6 tabs
- `saveSection(1)` → `savedSections` contiene 1, `editingSections` no lo contiene
- `statusFor(1)` con los campos de S1 llenos → retorna `'complete'`
- Sin errores TypeScript

---

### Task 04 — UI primitivos batch A

**Files:**
- `resources/js/components/UI/AppFormField.vue` (nuevo)
- `resources/js/components/UI/AppToggle.vue` (nuevo)
- `resources/js/components/UI/AppToggleCard.vue` (nuevo)
- `resources/js/components/UI/AppPillRadios.vue` (nuevo)

**`AppFormField.vue`:**
```typescript
// Props
defineProps<{
  label?: string
  required?: boolean
  optional?: boolean
  adminOnly?: boolean
  help?: string
  error?: string
  ok?: string
  col?: number   // 1-12, aplica clase col-{col} al wrapper
}>()
// Slot: default
```
- Wrapper `div.uf-field.col-{col}`
- Label con `<span class="req">*</span>` si required, `<span class="opt">opcional</span>` si optional, `<span class="admin-only">solo admin</span>` si adminOnly
- Debajo del slot: `div.uf-error` si error, `div.uf-ok` si ok (sin error), `div.uf-help` si help (sin error ni ok)

**`AppToggle.vue`:**
```typescript
defineProps<{ modelValue: boolean; label?: string; sub?: string; disabled?: boolean }>()
defineEmits<{ 'update:modelValue': [boolean] }>()
```
- `<label class="uf-toggle">` con `<input type="checkbox">` + `<span class="track"/>` + label+sub opcionales

**`AppToggleCard.vue`:**
```typescript
defineProps<{ modelValue: boolean; label: string; sub?: string }>()
defineEmits<{ 'update:modelValue': [boolean] }>()
```
- `<label class="uf-toggle-card" :class="{ checked: modelValue }">`
- Texto a la izquierda, toggle a la derecha
- Thumb se anima: left 2px → 16px al activarse

**`AppPillRadios.vue`:**
```typescript
defineProps<{
  modelValue: string
  options: string[] | Array<{ key: string; label: string }>
}>()
defineEmits<{ 'update:modelValue': [string] }>()
```
- `div.uf-pill-radios` con `label.uf-pill-radio` por opción. `:class="{ checked: modelValue === k }"`

**Done criteria:**
- Los 4 componentes se renderizan sin errores en una página de prueba
- `AppToggleCard` animación del thumb funciona
- `AppFormField` con `col=4` renderiza `div.uf-field.col-4`
- Sin errores TypeScript

---

### Task 05 — UI primitivos batch B

**Files:**
- `resources/js/components/UI/AppDocInput.vue` (nuevo)
- `resources/js/components/UI/AppTelInput.vue` (nuevo)
- `resources/js/components/UI/AppPasswordInput.vue` (nuevo)
- `resources/js/components/UI/AppPasswordStrength.vue` (nuevo)

**`AppDocInput.vue`:**
```typescript
defineProps<{ type: string; number: string }>()
defineEmits<{ 'update:type': [string]; 'update:number': [string] }>()
```
- `div.uf-id-combo`: select con `UF_DOC_TYPES` + input text
- Input filtra caracteres no numéricos en el handler: `v.replace(/[^\d.]/g, '')`

**`AppTelInput.vue`:**
```typescript
defineProps<{ modelValue: string; dialCode: string }>()
defineEmits<{ 'update:modelValue': [string]; 'update:dialCode': [string] }>()
```
- `div.uf-tel-combo`: select con códigos de `UF_COUNTRIES` (opción = `c.dial`, texto = `c.dial`) + input tel

**`AppPasswordInput.vue`:**
```typescript
defineProps<{ modelValue: string; placeholder?: string }>()
defineEmits<{ 'update:modelValue': [string] }>()
```
- `div.uf-input-wrap.has-right`: input con type dinámico (password/text) + botón con AppIcon (eye/eyeOff)
- Cuando `show === false`: `font-family: var(--font-mono)`, `letter-spacing: 0.06em`

**`AppPasswordStrength.vue`:**
```typescript
defineProps<{ value: string }>()
```
- Calcula score (0–5) según: length≥8, length≥12, mayúsculas+minúsculas, dígito, símbolo
- 5 barras `div` con color según score: 0-1 = danger, 2-3 = warning, 4-5 = success
- Texto a la derecha: 'Muy débil' / 'Débil' / 'Aceptable' / 'Buena' / 'Fuerte' / 'Muy fuerte'
- Si `value === ''` → renderiza `div.uf-help` con texto de sugerencia

**Done criteria:**
- `AppDocInput` filtra letras correctamente
- `AppPasswordInput` alterna type texto/password al hacer click en el ícono
- `AppPasswordStrength` muestra 5 barras con color correcto para cadenas de ejemplo
- Sin errores TypeScript

---

### Task 06 — UI primitivos batch C

**Files:**
- `resources/js/components/UI/AppRepeatable.vue` (nuevo)
- `resources/js/components/UI/AppFileZone.vue` (nuevo)
- `resources/js/components/UI/AppAvatarUpload.vue` (nuevo)

**`AppRepeatable.vue`:**
```typescript
defineProps<{
  items: any[]
  titlePrefix?: string
  emptyTitle?: string
  emptySub?: string
  addLabel?: string
}>()
defineEmits<{ add: []; remove: [number] }>()
// Scoped slot: item(item: any, index: number)
```
- Si `items.length === 0`: estado vacío con ícono `+`, `emptyTitle`, `emptySub`
- Por cada item: `div.uf-repeat-item` con cabecera (`uf-repeat-num` + `uf-repeat-title` + pill "Principal" si `item.__main` + botón trash)
- `div.uf-repeat-body` con slot `item`
- Al final: `button.uf-repeat-add` con AppIcon `plus` + `addLabel`

**`AppFileZone.vue`:**
```typescript
defineProps<{ filename?: string; accept?: string }>()
defineEmits<{ 'update:filename': [string] }>()
```
- `div.uf-file-zone` clickable → emite `update:filename` con `'demo.pdf'` (prototype)
- Muestra ícono upload + texto: filename si hay, "Arrastrá un archivo o tocá para subir" si no
- Subtexto: `accept` prop (default `'PDF, JPG, PNG · máx. 5 MB'`)

**`AppAvatarUpload.vue`:**
```typescript
defineProps<{ initials?: string; hasImage?: boolean }>()
defineEmits<{ upload: []; remove: [] }>()
```
- Círculo/cuadrado: si hasImage → muestra iniciales en mayúsculas, si no → AppIcon `user`
- Metadata: label "Foto de perfil" + sub con especificaciones + botones "Subir"/"Cambiar" + "Quitar" (si hasImage)

**Done criteria:**
- `AppRepeatable` renderiza estado vacío correctamente
- Agregar un item → aparece card con cabecera y slot
- Eliminar item → emite `remove(index)`
- `AppFileZone` al hacer click → emite filename
- Sin errores TypeScript

---

### Task 07 — Chrome A: `UserFormRolePicker` + `UserFormHeader`

**Files:**
- `resources/js/components/security/UserForm/UserFormRolePicker.vue` (nuevo)
- `resources/js/components/security/UserForm/UserFormHeader.vue` (nuevo)

**`UserFormRolePicker.vue`:**
```typescript
defineEmits<{ pick: [RoleKey] }>()
```
- Estado interno: `selectedRole = ref<RoleKey | ''>('')`
- Cabecera: `div.uf-role-screen-head` con "Paso 1 de 2 · Elegir rol", h1, descripción
- Grid de 4 cards: `div.uf-role-grid` → `button.uf-role-card` por rol
  - Checkmark visible cuando está seleccionado (`:class="{ selected: selectedRole === r.key }"`)
  - Ícono del rol (SVG inline para admin/student/professor/guardian — ver diseño)
  - h3 con nombre, p con descripción, div con `ROLE_SECTION_COUNT[r.key]` secciones
  - `@click` → selecciona, `@dblclick` → emite `pick(r.key)` directamente
- Footer: `div.uf-role-actions`
  - Botón "Cancelar" → `router.visit(index().url())` (Wayfinder)
  - Botón "Continuar con {rol}" → disabled si no hay selección → emite `pick(selectedRole)`

**`UserFormHeader.vue`:**
```typescript
defineProps<{
  role: RoleKey
  isEdit?: boolean
  completion: { pct: number }
  autosave: { status: 'idle' | 'saving' | 'saved'; when: Date | null }
}>()
defineEmits<{ changeRole: [] }>()
```
- Título h1: "Nuevo usuario" / "Editar usuario" + `span.uf-mode-tag` con "MODO CREACIÓN"/"MODO EDICIÓN"
- Descripción del modo
- Pill del rol: `span.uf-role-pill` con ícono + "Rol: **{label}**" + `button.change` que emite `changeRole`
- Barra de progreso: track + fill (`width: {pct}%`) + porcentaje
- Autosave: dot con clase CSS según status + texto

**Done criteria:**
- Role picker muestra 4 cards, selección funciona, doble click emite pick sin necesitar botón
- Header muestra progreso con barra animada
- Autosave dot cambia de color según status
- Sin errores TypeScript

---

### Task 08 — Chrome B: `UserFormTabs` + `UserFormSideNav`

**Files:**
- `resources/js/components/security/UserForm/UserFormTabs.vue` (nuevo)
- `resources/js/components/security/UserForm/UserFormSideNav.vue` (nuevo)

**`UserFormTabs.vue`:**
```typescript
defineProps<{
  tabs: TabDef[]
  modelValue: string
  completion: { sectionsComplete: Set<number>; sectionsPartial: Set<number> }
}>()
defineEmits<{ 'update:modelValue': [string] }>()
```
- `div.uf-tabs-wrap > div.uf-tabs`
- Por cada tab: `button.uf-tab` con `:class="{ active: modelValue === tb.key, complete: allComplete }"`
  - `span.uf-tab-idx` con índice 01/02/…
  - Texto del label
  - Punto de advertencia `span.uf-tab-warn` si alguna sección empezada pero no todas completas

**`UserFormSideNav.vue`:**
```typescript
defineProps<{
  sections: number[]
  activeSection: number | null
  completion: { sectionsComplete: Set<number>; sectionsPartial: Set<number> }
  savedSections: Set<number>
  editingSections: Set<number>
}>()
defineEmits<{ scrollTo: [number] }>()
```
- `nav.uf-side-nav`
- Título `p.uf-side-nav-title`: "En esta pestaña"
- Por cada sección: `button.uf-side-link` con clases `active`/`complete`/`partial` según estado
  - `span.uf-side-status` (dot) + nombre de la sección desde `UF_SECTIONS[n].title`
  - `@click` → emite `scrollTo(n)`

**Done criteria:**
- Tab activo tiene clase `active`, tab completo tiene clase `complete`
- Sidebar muestra dot correcto para sección vacía/parcial/completa
- Sin errores TypeScript

---

### Task 09 — Chrome C: `UserFormSection` + `UserFormFooter`

**Files:**
- `resources/js/components/security/UserForm/UserFormSection.vue` (nuevo)
- `resources/js/components/security/UserForm/UserFormFooter.vue` (nuevo)

**`UserFormSection.vue`:**
```typescript
defineProps<{
  num: string         // '01', '02', etc.
  title: string
  sub?: string
  note?: string
  roleTag?: { label: string; cond: boolean } | null
  comfy?: boolean
  status: SectionStatus
  saved?: { at: Date } | null
  readonly?: boolean
  sectionId: number
}>()
defineEmits<{ save: []; enterEdit: []; cancel: [] }>()
// Slot: default
```
- `<section :id="'sec-' + sectionId" class="uf-section" :class="{ complete: status === 'complete', readonly }">` 
- Head: número, título, `span.uf-section-role-tag` si roleTag, badge "Guardado" + ícono check si saved
- Si `readonly`: botón "Editar" con AppIcon pencil → emite `enterEdit`
- Body: `div.uf-section-body` con clase `comfy` si prop comfy
- Foot (si no readonly): texto de estado + botones Descartar (si editing) + "Guardar sección"

**`UserFormFooter.vue`:**
```typescript
defineProps<{
  completion: { pct: number; sectionsComplete: Set<number> }
  totalSections: number
  autosave: { status: string; when: Date | null }
}>()
defineEmits<{ discard: []; saveDraft: []; submit: [] }>()
```
- `div.uf-footer-bar` sticky
- Izquierda: texto de progreso (`{pct}% completo · N de M secciones`) + separador + autosave
- Derecha: btn "Descartar" (ghost) + btn "Guardar borrador" (secondary) + btn "Crear usuario" (primary, disabled si pct < 50)
- Botón "Crear usuario": ícono check + texto

**Done criteria:**
- `UserFormSection` con `status='complete'` tiene clase `complete` en el `<section>`
- `id="sec-{sectionId}"` presente para el scroll spy
- Footer botón "Crear usuario" deshabilitado cuando pct < 50
- Sin errores TypeScript

---

### Task 10 — Página `Create.vue`

**Files:**
- `resources/js/pages/security/Users/Create.vue` (nuevo)

**Estructura:**
```vue
<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { useUserForm } from '@/composables/forms/useUserForm'
import UserFormRolePicker from '@/components/security/UserForm/UserFormRolePicker.vue'
import UserFormHeader from '@/components/security/UserForm/UserFormHeader.vue'
import UserFormTabs from '@/components/security/UserForm/UserFormTabs.vue'
import UserFormSideNav from '@/components/security/UserForm/UserFormSideNav.vue'
import UserFormSection from '@/components/security/UserForm/UserFormSection.vue'
import UserFormFooter from '@/components/security/UserForm/UserFormFooter.vue'
// Importar todas las secciones S01–S17

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Seguridad', href: '#' },
      { title: 'Usuarios', href: index().url() },
      { title: 'Nuevo', href: create().url() },
    ],
  },
})

const {
  activeRole, activeTab, activeSection, formData,
  savedSections, editingSections, autosave,
  tabs, activeTabDef, completion,
  pickRole, resetRole,
  saveSection, editSection, cancelSection, statusFor,
  scrollToSection, setupScrollSpy,
} = useUserForm()

// Scroll spy — setup cuando cambia el tab o el rol
let cleanupSpy: (() => void) | null = null
watch(activeTabDef, () => {
  cleanupSpy?.()
  if (!activeTabDef.value) return
  // nextTick para dar tiempo al DOM
  setTimeout(() => {
    cleanupSpy = setupScrollSpy(activeTabDef.value.sections)
  }, 50)
}, { immediate: true })
onUnmounted(() => cleanupSpy?.())
</script>

<template>
  <Head title="Nuevo usuario" />

  <div class="up-content">
    <!-- Step 0: Role picker -->
    <UserFormRolePicker v-if="!activeRole" @pick="pickRole" />

    <!-- Formulario -->
    <template v-else>
      <UserFormHeader
        :role="activeRole"
        :completion="completion"
        :autosave="autosave"
        @change-role="resetRole"
      />

      <UserFormTabs v-model="activeTab" :tabs="tabs" :completion="completion" />

      <div class="uf-form-layout">
        <UserFormSideNav
          :sections="activeTabDef?.sections ?? []"
          :active-section="activeSection"
          :completion="completion"
          :saved-sections="savedSections"
          :editing-sections="editingSections"
          @scroll-to="scrollToSection"
        />

        <div class="uf-sections">
          <UserFormSection
            v-for="secNum in (activeTabDef?.sections ?? [])"
            :key="secNum"
            :num="String(secNum).padStart(2, '0')"
            :title="UF_SECTIONS[secNum].title"
            :sub="UF_SECTIONS[secNum].sub"
            :note="UF_SECTIONS[secNum].note"
            :comfy="UF_SECTIONS[secNum].comfy"
            :status="statusFor(secNum)"
            :saved="savedSections.has(secNum) ? { at: new Date() } : null"
            :section-id="secNum"
            @save="saveSection(secNum)"
            @enter-edit="editSection(secNum)"
            @cancel="cancelSection(secNum)"
          >
            <!-- Dispatcher de sección — switch en el template -->
            <UserFormS01Identity  v-if="secNum === 1"  :data="formData" />
            <UserFormS02Credentials v-else-if="secNum === 2" :data="formData" />
            <!-- ... S03–S17 -->
          </UserFormSection>
        </div>
      </div>

      <UserFormFooter
        :completion="completion"
        :total-sections="tabs.flatMap(t => t.sections).length"
        :autosave="autosave"
        @discard="router.visit(index().url())"
        @save-draft="() => {}"
        @submit="() => {}"
      />
    </template>
  </div>
</template>
```

**Done criteria:**
- Visitar `/security/users/create` muestra el role picker
- Elegir "Estudiante" → formulario con 6 tabs
- Cambiar de tab → secciones correctas aparecen
- Sidebar scroll spy activo (sección highlight al scrollear)
- Footer visible con progreso correcto

---

### Task 11 — Secciones S01 + S02

**Files:**
- `resources/js/components/security/UserForm/UserFormS01Identity.vue`
- `resources/js/components/security/UserForm/UserFormS02Credentials.vue`

**Props comunes:**
```typescript
defineProps<{ data: UserFormData }>()
defineEmits<{ 'update:data': [Partial<UserFormData>] }>()
```
Nota: usar patrón `set(field, value)` donde `set = (k, v) => emit('update:data', { [k]: v })`.

**S01 — Identidad personal:**
- `AppAvatarUpload` con iniciales + `hasImage` derivado de si hay `firstName`
- `AppFormField` + input texto: Nombres (col=6), Apellidos (col=6)
- `AppFormField` + `AppDocInput`: Documento de identidad (col=6)
- `AppFormField` + input date: Fecha de nacimiento (col=3)
- `AppFormField` + select: Género con `UF_GENDERS` (col=3)
- `AppFormField` + select: Nacionalidad con `UF_COUNTRIES` (col=4)
- `AppFormField` + `AppTelInput`: Teléfono principal (col=4), Teléfono secundario opcional (col=4)

**S02 — Credenciales de acceso:**
- `AppFormField` + input email + ícono mail: Correo electrónico (col=6)
  - `ok="Formato válido"` si `data.email` pasa regex básico de email
- `AppFormField` + `AppPasswordInput`: Contraseña (col=6)
- `AppPasswordStrength` debajo del password field

**Done criteria:**
- S01 muestra avatar con iniciales cuando hay datos en firstName/lastName
- S02 muestra "Formato válido" en verde cuando el email tiene formato correcto
- Strength meter cambia colores al escribir la contraseña
- Sin errores TypeScript

---

### Task 12 — Sección S03 Dirección

**Files:**
- `resources/js/components/security/UserForm/UserFormS03Address.vue`

- Usa `AppRepeatable` con `addLabel="Agregar otra dirección"`, `titlePrefix="Dirección"`, `emptyTitle="Aún no agregaste una dirección"`, `emptySub="Agregá al menos una. Si tenés varias, marcá una como principal."`
- `@add` → push a `data.addresses` con `{ __id: Date.now(), country: 've', primary: items.length === 0 }`
- `@remove(i)` → filter
- Slot item: componente inline (o extraer `AddressItemForm.vue` si queda muy grande):
  - Select País `UF_COUNTRIES` (col=4), Estado `UF_STATES_VE` (col=4), Municipio `UF_MUNICIPIOS_DTTO` (col=4)
  - Select Parroquia (col=4), Zona `UF_ZONES` (col=4)
  - `AppToggle` "Es mi dirección principal" (col=4) — al activar, desactiva `primary` en todos los demás
  - Input texto Línea 1 required (col=12), Línea 2 opcional (col=12)

**Done criteria:**
- Agregar 2 direcciones funciona
- Marcar una como principal desmarca la otra
- Eliminar dirección funciona

---

### Task 13 — Sección S04 Demográfico

**Files:**
- `resources/js/components/security/UserForm/UserFormS04Demographic.vue`

- Subsección "Nacimiento": Ciudad (col=4), Estado `UF_STATES_VE` (col=4), País `UF_COUNTRIES` (col=4)
- Subsección "Identidad cultural":
  - `AppToggleCard` "Pertenece a un pueblo indígena" → condicional con Comunidad + `AppFormField`+select Lengua nativa `UF_LANGUAGES`
  - `AppToggleCard` "Es migrante retornado" → condicional con select País de procedencia
  - Select Religión `UF_RELIGIONS` (col=6, opcional)
  - `AppToggleCard` "Practica algún deporte" → condicional con input Deporte
  - Textarea "Actividades culturales" (col=12, opcional)

**Done criteria:**
- Toggle "indígena" muestra/oculta campos de comunidad y lengua
- Toggle "migrante" muestra/oculta campo de país
- Sin errores TypeScript

---

### Task 14 — Sección S05 Salud

**Files:**
- `resources/js/components/security/UserForm/UserFormS05Health.vue`

- Subsección "Generalidades": Grupo sanguíneo `UF_BLOOD` (col=3), Peso kg (col=3), Talla cm (col=3), IMC calculado (col=3)
  - IMC: si peso && talla → `div.uf-calc.ok` con "IMC: **{valor}**"
- Subsección "Condiciones especiales":
  - `AppToggleCard` "Tiene alguna discapacidad" → condicional: select Tipo `UF_DISABILITY_TYPES` + textarea Descripción
  - `AppToggleCard` "Tiene necesidades especiales" → condicional: textarea
  - Textarea Condición crónica (col=4, opcional), Medicación regular (col=4, opcional), Alergias (col=4, opcional)
- Subsección "Seguro":
  - `AppToggleCard` "Cuenta con seguro médico" → condicional: select Tipo `UF_INSURANCE`
- Subsección "Contacto de emergencia":
  - Input Nombre (col=5, required), `AppTelInput` Teléfono (col=4, required), Input Parentesco (col=3, required)

**Done criteria:**
- IMC se calcula y muestra cuando peso + talla están completos
- Discapacidad toggle revela campos
- Sin errores TypeScript

---

### Task 15 — Secciones S06 + S07

**Files:**
- `resources/js/components/security/UserForm/UserFormS06Consents.vue`
- `resources/js/components/security/UserForm/UserFormS07Attachments.vue`

**S06 — Consentimientos:**
- `div.uf-policy-meta`: "Versión de política aceptada" + "v1.0 — vigente desde 2026-04-01"
- `div.uf-consent` con 4 items:
  - Tratamiento de datos personales (required, key=`consent_data`)
  - Uso de imagen (key=`consent_image`)
  - Contacto por WhatsApp (key=`consent_whatsapp`)
  - Contacto por correo electrónico (key=`consent_email`)
- Cada item: `label.uf-consent-item :class="{ checked }"` con checkbox + box + texto (title + sub)

**S07 — Documentos adjuntos:**
- `AppRepeatable` con slot que contiene:
  - Select Tipo `UF_ATTACH_TYPES` (col=5, required)
  - `AppFileZone` (col=7, required)
  - `AppToggle` "Verificado" (solo admin) — cuando se activa agrega verifiedBy + verifiedAt
  - Si verificado: `span.uf-ok` "Documento aceptado"

**Done criteria:**
- Consentimiento de datos se puede marcar con checkbox
- Label cambia visual (clase `checked`) al marcarse
- Documentos: agregar doc → aparece con file zone
- Sin errores TypeScript

---

### Task 16 — Secciones S08 + S09

**Files:**
- `resources/js/components/security/UserForm/UserFormS08Academic.vue`
- `resources/js/components/security/UserForm/UserFormS09PrevEducation.vue`

**S08 — Perfil académico (student):**
- Input Código de estudiante adminOnly (col=4), Select Estatus `UF_ACADEMIC_STATUSES` (col=4), Date inscripción (col=4)
- Select Modalidad `UF_STUDY_MODALITIES` (col=4), `AppPillRadios` Turno `UF_SHIFTS` (col=4), Select Admisión `UF_ADMISSION` (col=4)
- Input number GPA affix `/ 20` (col=4), Select Grado `UF_GRADES` (col=4)

**S09 — Antecedentes educativos (student):**
- Subsección "Institución previa":
  - Input Institución (col=6), Select Tipo `UF_INSTITUTION_TYPES` (col=3), Input number Año egreso (col=3)
  - Input number Promedio affix `/20` (col=4), Select Motivo traslado `UF_TRANSFER_REASONS` (col=4), Select Nivel digital `UF_DIGITAL_LEVELS` (col=4)
- Subsección "Historia académica":
  - `AppToggleCard` "Repitió algún grado" → condicional: input descripción
  - `AppToggleCard` "Tiene estudios universitarios previos" → condicional: textarea
- Subsección "Educación del grupo familiar":
  - Select Nivel educativo madre `UF_EDU_LEVELS` (col=6), padre (col=6)

**Done criteria:**
- Todos los campos renderizan correctamente
- Condicionales funcionan
- Sin errores TypeScript

---

### Task 17 — Sección S10 Idiomas

**Files:**
- `resources/js/components/security/UserForm/UserFormS10Languages.vue`

- `AppRepeatable` con `addLabel="Agregar idioma"`, `titlePrefix="Idioma"`, `emptyTitle="Sin idiomas registrados"`
- Slot item:
  - Select Idioma `UF_LANGUAGES` (col=5, required)
  - Select Nivel `UF_LANG_LEVELS` (col=5, required)
  - `AppToggle` "Lengua materna" (col=2)

**Done criteria:**
- Agregar 2 idiomas funciona
- Toggle lengua materna funciona
- Sin errores TypeScript

---

### Task 18 — Secciones S11 + S12

**Files:**
- `resources/js/components/security/UserForm/UserFormS11Family.vue`
- `resources/js/components/security/UserForm/UserFormS12Socioeconomic.vue`

**S11 — Perfil familiar:**
- Select Estado civil representante `UF_MARITAL` (col=6), Input number Hijos (col=3), Input number Hermanos (col=3)
- Input number Posición hermanos (col=4), Select Arreglo convivencia `UF_LIVING` (col=8)
- Select Tipo jefe hogar `UF_HOUSEHOLD_HEAD` (col=6), Input Nombre jefe hogar (col=6)

**S12 — Perfil socioeconómico:**
- Subsección "Ingresos del hogar":
  - Select Rango ingreso `UF_INCOME_RANGES` (col=6), Select Fuente `UF_INCOME_SOURCES` (col=6)
  - Input number Personas que aportan (col=6), Date estudio socioeconómico adminOnly (col=6)
  - `AppToggleCard` "Recibe remesas" → condicional: select País `UF_COUNTRIES`
- Subsección "Empleo y becas del estudiante":
  - `AppToggleCard` "El estudiante trabaja" → condicional: select Tipo empleo `UF_EMPLOYMENT_TYPES` + input Horas semanales affix `hs`
  - `AppToggleCard` "Tiene beca externa" → condicional: input Nombre beca
  - `AppToggleCard` "Recibe beneficios institucionales" (sin condicional, informativo)

**Done criteria:**
- Todos los toggles condicionales funcionan
- Sin errores TypeScript

---

### Task 19 — Secciones S13 + S14

**Files:**
- `resources/js/components/security/UserForm/UserFormS13Benefits.vue`
- `resources/js/components/security/UserForm/UserFormS14Housing.vue`

**S13 — Beneficios (repetible):**
- `AppRepeatable` con `addLabel="Agregar beneficio"`, `emptyTitle="Sin beneficios registrados"`
- Slot: Select Beneficio `UF_BENEFITS` (col=6, required), `AppToggle` "Actualmente activo" (col=6)
- Date inicio (col=6, opcional), Date fin (col=6, opcional, help "Dejá vacío si sigue activo")

**S14 — Vivienda:**
- Subsección "Tipo y tenencia": Select `UF_HOUSING` (col=4), Select `UF_TENURE` (col=4), Select `UF_CONSTRUCTION` (col=4)
- Subsección "Composición del hogar":
  - Input number Habitaciones (col=4), Baños (col=4), Personas en el hogar (col=4)
  - Si rooms && peopleHome: `div.uf-calc :class="{ ok: ratio<=2.5, warn: ratio>2.5 }"` con índice personas/habitación + texto hacinamiento
- Subsección "Traslado": Select Tiempo traslado `UF_COMMUTE` (col=6), Select Transporte `UF_TRANSPORT` (col=6)
- Subsección "Servicios básicos":
  - 4 tiles checkbox: Agua, Electricidad, Gas, Internet (`div.uf-services` con `label.uf-service-tile`)
  - Input "Otros servicios" (col=12, opcional)

**Done criteria:**
- Calculadora de hacinamiento funciona: 5 personas / 2 habitaciones → "Hacinamiento (> 2,5)" en naranja
- Tiles de servicios básicos se marcan/desmarcan
- Sin errores TypeScript

---

### Task 20 — Sección S15 Representantes

**Files:**
- `resources/js/components/security/UserForm/UserFormS15Guardians.vue`

- `AppRepeatable` con `addLabel="Agregar representante"`, `emptyTitle="Sin representantes asignados"`, `emptySub="Buscá un usuario tipo guardian existente o creá uno nuevo."`
- Slot item:
  - Campo de búsqueda: `div.uf-search-row` con input (ícono search) + separador "o" + botón "Crear nuevo"
  - Si hay `g.search`: mostrar resultado simulado (card con avatar, nombre, doc, badge "Vinculado")
  - Select Parentesco `UF_KINSHIP` (col=4, required)
  - `AppToggle` "Representante principal" (col=4) — solo uno puede ser principal, al activar desactiva los demás
  - `AppToggle` "Contacto de emergencia" (col=4)

**Done criteria:**
- Escribir en el input de búsqueda muestra el resultado simulado
- Marcar "principal" en uno desmarca el anterior
- Sin errores TypeScript

---

### Task 21 — Secciones S16 + S17

**Files:**
- `resources/js/components/security/UserForm/UserFormS16GuardianProfile.vue`
- `resources/js/components/security/UserForm/UserFormS17ProfessorProfile.vue`

**S16 — Perfil del representante (guardian):**
- Input Ocupación (col=6), Input Empleador (col=6)
- `AppTelInput` Teléfono del trabajo (col=6, opcional), Select Estado civil `UF_MARITAL` (col=6)
- Select Nivel educativo `UF_EDU_LEVELS` (col=12)

**S17 — Perfil del personal (professor):**
- Subsección "Datos laborales":
  - Input Código de empleado adminOnly (col=4), Input Título académico (col=4), Input Especialidad (col=4)
- Subsección "Contrato y dedicación":
  - Select Tipo contrato `UF_CONTRACT` (col=4), Select Dedicación `UF_DEDICATION` (col=4), Input number Carga horaria affix `hs` (col=4)
  - Date Fecha ingreso required (col=4), Date Fecha egreso opcional (col=4), Select Estatus laboral adminOnly `UF_EMPL_STATUS` (col=4)
- Subsección "Coordinación":
  - `AppToggleCard` "Es coordinador de departamento" → condicional:
    - Select Departamento `UF_DEPARTMENTS` (col=8), Date "Coordinador desde" (col=4)

**Done criteria:**
- S16 se renderiza correctamente para el rol `guardian`
- S17 toggle coordinación revela departamento y fecha
- Sin errores TypeScript

---

### Task 22 — Polish y verificación visual

**Files:** ninguno nuevo — verificación en browser

**Checklist:**
- [ ] Rol Admin: 3 tabs, secciones 1,2,3,4 + 5 + 7,6. Completion calcula correctamente
- [ ] Rol Estudiante: 6 tabs, todas las secciones. Scroll spy funciona en cada tab
- [ ] Rol Profesor: 4 tabs con S17. Coordinación condicional funciona
- [ ] Rol Representante: 4 tabs con S16. Formulario vacío y con datos
- [ ] Cambiar rol desde pill "cambiar" en el header vuelve al step 0
- [ ] Footer botón "Crear usuario" habilitado solo cuando ≥ 50% completado
- [ ] Autosave: al cambiar cualquier campo → "Guardando…" → "Guardado hace Xs"
- [ ] Mobile: topbar con hamburger aparece en viewport < 768px (si el layout lo provee)
- [ ] Sin errores en consola del navegador
- [ ] Ejecutar `vendor/bin/sail npm run build` sin errores TypeScript
