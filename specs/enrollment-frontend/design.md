# Diseño técnico — Enrollment Frontend Integration

**Feature:** `02-enrollment-frontend`

---

## Arquitectura general

```
GET /enrollment
    → EnrollmentController::index()
        → Period::activePeriod()
        → auto-crea Enrollment (draft) si no existe
        → BuildEnrollmentCatalogAction::handle(student, period, draft)
            → Subject[]  (pensum activo)
                → Section[] (del period activo)
                    → Schedule[] (con professor.user + classrooms)
                    → enrolledCount (subquery)
            → PrerequisiteValidator::canTake() por materia
            → completedSubjectIds (query a enrollment_details aprobadas)
        → EnrollmentCatalogSubjectResource::collection()
        → Inertia::render('enrollment/Index', props)

Index.vue
    defineProps → enrollment, catalog, rules, can
    backendToCatalog(catalog) → EnrollmentSubject[]  (tipos UI existentes)
    initSelectionsFromDraft(enrollment) → EnrollmentSelections
    useEnrollmentState(subjects, selections)  — sin cambios
    useEnrollmentFilters(subjects)             — sin cambios
    useEnrollmentForm()                        — nuevo
    useEnrollmentPermissions()                 — nuevo
```

---

## Cambios en backend

### 1. `Section` — añadir relaciones

```php
// app/Models/Section.php — añadir:
public function schedules(): HasMany {
    return $this->hasMany(Schedule::class);
}
public function enrollmentDetails(): HasMany {
    return $this->hasMany(EnrollmentDetail::class);
}
```

### 2. `Subject` — añadir relación

```php
// app/Models/Subject.php — añadir:
public function sections(): HasMany {
    return $this->hasMany(Section::class);
}
```

### 3. `BuildEnrollmentCatalogAction` (nuevo)

**Ubicación:** `app/Actions/Enrollment/BuildEnrollmentCatalogAction.php`

**Firma:**
```php
public function handle(Student $student, Period $period, Enrollment $draft): Collection
```

**Responsabilidades:**
- Carga las `Subject` del pensum del estudiante con eager-loading de:
  `sections` (filtradas por `period_id`) → `schedules.professor.user`, `theoryClassroom`, `labClassroom`
- Agrega a cada sección el conteo de inscritos activos:
  `Section::withCount(['enrollmentDetails as enrolled' => fn($q) => $q->whereIn('status', ['draft', 'confirmed'])])`
- Consulta las IDs de materias completadas del estudiante:
  `EnrollmentDetail::whereHas('enrollment', fn($q) => $q->where('student_id', $student->id)->where('status', 'approved'))->where('status', 'confirmed')->pluck('subject_id')`
- Para cada Subject: llama `PrerequisiteValidator::canTake($student, $subject)`
- Extrae del `$draft` las secciones ya seleccionadas (detailsBySubjectId)
- Retorna `Collection` de arrays con estructura compatible con `EnrollmentCatalogSubjectResource`

**Retorna:** `Collection<int, array{subject: Subject, prereqs_ok: bool, completed: bool, recommended_trim: bool, selected_section_id: ?int, sections: Collection<Section>}>`

### 4. `EnrollmentCatalogSubjectResource` (nuevo)

**Ubicación:** `app/Http/Resources/Enrollment/EnrollmentCatalogSubjectResource.php`

**Contrato de salida:**
```json
{
  "id": 1,
  "code": "ALG-302",
  "name": "Algoritmos y Estructuras",
  "credits": 4,
  "type": "oblig",
  "recommended_trim": true,
  "prereqs_ok": true,
  "completed": false,
  "description": "",
  "selected_section_id": null,
  "sections": [
    {
      "id": 5,
      "code": "3-A",
      "professor": { "id": 1, "name": "Marco Salas", "initials": "MS" },
      "room": "Aula 201",
      "modality": "Teórica",
      "capacity": 35,
      "enrolled": 32,
      "is_full": false,
      "slots": [
        { "day": 0, "start": "07:00", "end": "09:00" }
      ],
      "no_schedule": false
    }
  ]
}
```

**Derivaciones:**

| Campo | Lógica |
|-------|--------|
| `type` | Hardcoded `"oblig"` (Subject no tiene campo type; TODO futuro) |
| `modality` | Todos `theory` → `"Teórica"`; todos `lab` → `"Laboratorio"`; ambos → `"Mixta"`; sin schedules → `"Por definir"` |
| `room` | `theoryClassroom.identifier ?? labClassroom.identifier ?? "Por asignar"` |
| `professor` | Primer `schedule.professor` → si no → `section.mainTeacher` → si no → `{id:0, name:"Por asignar", initials:"··"}` |
| `initials` | Primera letra de cada palabra del nombre, max 2, mayúsculas |
| `slots[].day` | `DayOfWeek::order() - 1` → 0-indexed (Lunes=0, Sábado=5) |
| `slots[].start/end` | `Carbon::parse($time)->format('H:i')` |
| `is_full` | `$section->enrolled >= $section->capacity` |
| `no_schedule` | `$section->schedules->isEmpty()` |

**Nota sobre `$this->resource`:** La Resource recibe el array devuelto por `BuildEnrollmentCatalogAction`. Accede via `$this->resource['subject']`, `$this->resource['prereqs_ok']`, etc.

### 5. `EnrollmentController::index` — refactorizar

**Lógica nueva:**

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $student = $this->resolveStudent($user, $request->input('student_id'));

    // resolveStudent: si es student → $user->student
    //                si es guardian → valida que student_id sea asignado
    //                si guardian sin student_id → primer asignado (o retornar guardian_students)

    $period = Period::where('status', PeriodStatus::Active)->first();

    if (! $period || ! $student?->current_pensum_id) {
        return Inertia::render('enrollment/Index', [
            'enrollment' => null,
            'catalog'    => [],
            'rules'      => $this->buildRules(null, $student),
            'can'        => ['confirm' => false],
        ]);
    }

    // Auto-crear draft si no existe
    $draft = Enrollment::firstOrCreate(
        ['student_id' => $student->id, 'period_id' => $period->id],
        ['pensum_id' => $student->current_pensum_id, 'status' => EnrollmentStatus::Draft, 'uc_disponibles' => 0, 'uc_inscritas' => 0]
    );
    $draft->load(['details.subject', 'details.section', 'period', 'pensum', 'student.user']);

    $catalog = app(BuildEnrollmentCatalogAction::class)->handle($student, $period, $draft);

    return Inertia::render('enrollment/Index', [
        'enrollment' => new EnrollmentResource($draft),
        'catalog'    => EnrollmentCatalogSubjectResource::collection($catalog)->resolve(),
        'rules'      => $this->buildRules($period, $student),
        'can'        => ['confirm' => $user->can('confirm', $draft)],
    ]);
}

private function buildRules(?Period $period, ?Student $student): array
{
    return [
        'period'       => $period?->name,
        'deadline'     => null, // expandir si Period agrega enrollment_deadline
        'days_left'    => 0,
        'credits_min'  => 12,
        'credits_max'  => 24,
        'student_name' => $student?->user->name ?? '',
        'student_code' => $student?->user->email ?? '',
        'career'       => $student?->pensum?->career?->name ?? '',
        'trimester'    => $student?->academic_year ? "{$student->academic_year}er trimestre" : '',
    ];
}
```

---

## Cambios en frontend

### 6. `types/enrollment.ts` — añadir tipos backend (sin eliminar tipos existentes)

```typescript
// --- Backend prop types (mirror Resources) ---

export interface BackendEnrollmentSlot {
    day: number      // 0=Lunes…5=Sábado
    start: string    // "07:00"
    end: string      // "09:00"
}

export interface BackendEnrollmentSection {
    id: number
    code: string
    professor: { id: number; name: string; initials: string }
    room: string
    modality: 'Teórica' | 'Práctica' | 'Mixta' | 'Laboratorio' | 'Por definir'
    capacity: number
    enrolled: number
    is_full: boolean
    slots: BackendEnrollmentSlot[]
    no_schedule: boolean
}

export interface BackendEnrollmentSubject {
    id: number
    code: string
    name: string
    credits: number
    type: 'oblig' | 'electiva'
    recommended_trim: boolean
    prereqs_ok: boolean
    completed: boolean
    description: string
    selected_section_id: number | null
    sections: BackendEnrollmentSection[]
}

export interface BackendEnrollmentRules {
    period: string | null
    deadline: string | null
    days_left: number
    credits_min: number
    credits_max: number
    student_name: string
    student_code: string
    career: string
    trimester: string
}

export interface BackendEnrollmentDetail {
    id: number
    enrollment_id: number
    subject_id: number
    section_id: number
    subject: { id: number; code: string; name: string; credits_uc: number }
    section: { id: number; code: string; capacity: number }
    status: string
}

export interface BackendEnrollment {
    id: number
    student_id: number
    period: string
    pensum: string
    uc_disponibles: number
    uc_inscritas: number
    status: string
    details: BackendEnrollmentDetail[]
}

// --- Mapper: BackendEnrollmentSubject[] → EnrollmentSubject[] ---
// Función pura exportada desde types/enrollment.ts o composables/enrollment/enrollmentMappers.ts
// export function backendToCatalog(items: BackendEnrollmentSubject[]): EnrollmentSubject[]
// Mapea campo por campo: credits_uc→credits, prereqs_ok→prereqsOk, is_full→enrolled=capacity
// slots.day ya es 0-indexed — sin transformación adicional
```

### 7. `composables/enrollment/useEnrollmentForm.ts` (nuevo)

```typescript
// Responsabilidades:
//   createEnrollment()              — POST /enrollment (si enrollment.value === null)
//   addSubject(subjectId, sectionId)  — POST /enrollment/{id}/detail
//   removeSubject(detailId)          — DELETE /enrollment/{id}/detail/{detailId}
//   confirmEnrollment()              — POST /enrollment/{id}/confirm → router.reload()

// Estado expuesto:
//   isLoading: Ref<boolean>
//   error: Ref<string | null>
//   clearError(): void

// Rutas via Wayfinder:
//   import { store, detail, confirm } from '@/routes/enrollment'
//   (o desde @/actions si Wayfinder genera a partir del controller)

// Manejo de errores:
//   422 → error.value = response.data.error
//   401 → router.visit('/login')
//   Otros → error.value = 'Error inesperado. Intenta de nuevo.'
```

**Flujo de `addSubject`:**
1. Si `enrollment.value === null` → llamar `createEnrollment()` primero
2. `useHttp().post(detail.store.url({enrollment: enrollmentId}), {subject_id, section_id})`
3. En éxito: actualizar `selections.value[subjectCode] = sectionIdx` localmente
4. En 422: `error.value = response.data.error` (NO actualizar selections)

**Flujo de `removeSubject`:**
1. `useHttp().delete(detail.destroy.url({enrollment: enrollmentId, enrollmentDetail: detailId}))`
2. En éxito: `delete selections.value[subjectCode]` localmente
3. En error: mostrar error sin cambios

**Flujo de `confirmEnrollment`:**
1. `useHttp().post(confirm.url({enrollment: enrollmentId}))`
2. En éxito: `router.reload()`
3. En 422: `error.value = response.data.error`

### 8. `composables/enrollment/useEnrollmentPermissions.ts` (nuevo)

```typescript
// Recibe: enrollment: Ref<BackendEnrollment | null>, can: { confirm: boolean }
// Expone:
//   canConfirm: ComputedRef<boolean>   — can.confirm Y enrollment.status === 'draft'
//   canEdit: ComputedRef<boolean>      — enrollment.status === 'draft'
//   isReadOnly: ComputedRef<boolean>   — enrollment.status !== 'draft' || enrollment === null
```

### 9. `pages/enrollment/Index.vue` — cambios

**Props:**
```typescript
defineProps<{
    enrollment: BackendEnrollment | null
    catalog: BackendEnrollmentSubject[]
    rules: BackendEnrollmentRules
    can: { confirm: boolean }
}>()
```

**Inicialización:**
```typescript
// En lugar de: const subjects = ENROLLMENT_SUBJECTS
const subjects = computed(() => backendToCatalog(props.catalog))

// En lugar de: selections.value = { ...ENROLLMENT_INITIAL_SELECTIONS }
const selections = ref<EnrollmentSelections>(
    Object.fromEntries(
        (props.enrollment?.details ?? [])
            .filter(d => d.status !== 'rejected')
            .map(d => {
                const subjectCode = d.subject.code
                const subject = props.catalog.find(s => s.id === d.subject_id)
                const sectionIdx = subject?.sections.findIndex(s => s.id === d.section_id) ?? 0
                return [subjectCode, sectionIdx]
            })
    )
)
```

**Conectar operaciones:**
```typescript
// En lugar de: const { search, filters, filteredSubjects } = useEnrollmentFilters(ENROLLMENT_SUBJECTS)
const { search, filters, filteredSubjects } = useEnrollmentFilters(subjects.value)

const { isLoading, error, addSubject, removeSubject, confirmEnrollment } = useEnrollmentForm(
    toRef(props, 'enrollment'),
    selections
)
const { canConfirm, isReadOnly } = useEnrollmentPermissions(
    toRef(props, 'enrollment'),
    props.can
)

async function handleSelect(code: string, sectionIdx: number): Promise<void> {
    const subject = props.catalog.find(s => s.code === code)
    if (!subject) return
    const section = subject.sections[sectionIdx]
    if (!section) return
    await addSubject(subject.id, section.id, code, sectionIdx)
}

async function handleUnselect(code: string): Promise<void> {
    const detail = props.enrollment?.details.find(d => d.subject.code === code && d.status !== 'rejected')
    if (!detail) { delete selections.value[code]; return }
    await removeSubject(detail.id, code)
}

async function handleConfirm(): Promise<void> {
    await confirmEnrollment()
}
```

**En el template:**
- Pasar `isReadOnly` a `EnrollmentMateriaRow` y `EnrollmentSummaryPanel` para deshabilitar interacciones
- Mostrar `error` en `EnrollmentSummaryPanel` (ya tiene el slot para esto)
- Reemplazar `ENROLLMENT_RULES` → `rules` (mapeado al tipo `EnrollmentRules` existente)
- Eliminar imports de `enrollmentMockData`

---

## Archivos nuevos

| Archivo | Tipo |
|---------|------|
| `app/Actions/Enrollment/BuildEnrollmentCatalogAction.php` | Action |
| `app/Http/Resources/Enrollment/EnrollmentCatalogSubjectResource.php` | Resource |
| `resources/js/composables/enrollment/useEnrollmentForm.ts` | Composable |
| `resources/js/composables/enrollment/useEnrollmentPermissions.ts` | Composable |

## Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `app/Models/Section.php` | Añadir `schedules()`, `enrollmentDetails()` |
| `app/Models/Subject.php` | Añadir `sections()` |
| `app/Http/Controllers/Enrollment/EnrollmentController.php` | Refactorizar `index()`, añadir `resolveStudent()`, `buildRules()` |
| `resources/js/types/enrollment.ts` | Añadir Backend* types y `backendToCatalog()` mapper |
| `resources/js/pages/enrollment/Index.vue` | Conectar a props reales, eliminar mock data |

## Archivos a eliminar

| Archivo | Razón |
|---------|-------|
| `resources/js/composables/enrollment/enrollmentMockData.ts` | Reemplazado por Inertia props |

---

## Decisiones de diseño

| Decisión | Elección | Razón |
|----------|----------|-------|
| Auto-creación del draft | Sí, en `index()` | Evita estado nulo en el formulario; simplifica el frontend |
| Carga inicial de datos | Inertia SSR props | Sin XHR adicional al cargar; compatible con prefetch |
| AJAX para operaciones | `useHttp` (Inertia v3) | Patrón del proyecto; sin Axios |
| Actualización de estado | Local state (optimistic para remove, pessimistic para add) | Add puede fallar (422); remove siempre funciona en draft |
| Mapper de tipos | `backendToCatalog()` en types/ | Aísla contrato backend del contrato UI existente |
| `completed` | Via EnrollmentDetail/Enrollment aprobados | Sin necesidad del módulo de notas |
| `type` de materia | Hardcoded `"oblig"` | No existe campo en Subject; migración futura |
| Confirmación | `router.reload()` post-confirm | El estado cambia a read-only; Inertia refetch necesario |
| `EnrollmentCatalogSubjectResource.$resource` | `array` (no Model) | BuildEnrollmentCatalogAction retorna computed arrays |
