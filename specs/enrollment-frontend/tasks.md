# Tasks — Enrollment Frontend Integration

**Feature:** `02-enrollment-frontend`
**Depende de:** `01-enrollment-backend` (completado)

---

## Progreso general

- [x] Task 1 — Modelos: añadir relaciones `Section::schedules()`, `Subject::sections()`
- [x] Task 2 — Backend: `BuildEnrollmentCatalogAction`
- [x] Task 3 — Backend: `EnrollmentCatalogSubjectResource`
- [x] Task 4 — Backend: Refactorizar `EnrollmentController::index()`
- [x] Task 5 — Backend: Feature tests para el nuevo `index`
- [x] Task 6 — Frontend: Tipos `Backend*` + función `backendToCatalog()` en `types/enrollment.ts`
- [x] Task 7 — Frontend: `useEnrollmentForm.ts`
- [x] Task 8 — Frontend: `useEnrollmentPermissions.ts`
- [x] Task 9 — Frontend: Conectar `Index.vue` a props reales y eliminar mock data

---

## Detalle de cada task

---

### Task 1 — Modelos: relaciones nuevas

**Archivos involucrados:**
- `app/Models/Section.php`
- `app/Models/Subject.php`

**Criterio de done:**
- `Section` tiene método `schedules(): HasMany` que retorna `Schedule::class`
- `Section` tiene método `enrollmentDetails(): HasMany` que retorna `EnrollmentDetail::class`
- `Subject` tiene método `sections(): HasMany` que retorna `Section::class`
- Pint no reporta errores de formato
- No se requiere migración (relaciones derivadas de FKs existentes)

---

### Task 2 — `BuildEnrollmentCatalogAction`

**Archivos involucrados:**
- `app/Actions/Enrollment/BuildEnrollmentCatalogAction.php` (nuevo)

**Firma del método:**
```php
public function handle(Student $student, Period $period, Enrollment $draft): Collection
```

**Lo que hace:**
1. Carga `$student->pensum->subjects` con eager-loading de secciones del período activo:
   ```php
   $subject->load([
       'sections' => fn($q) => $q->where('period_id', $period->id)
           ->withCount(['enrollmentDetails as enrolled' =>
               fn($q) => $q->whereIn('status', ['draft', 'confirmed'])])
           ->with(['schedules.professor.user', 'theoryClassroom', 'labClassroom']),
   ])
   ```
2. Consulta completed subjects del estudiante:
   ```php
   EnrollmentDetail::whereHas('enrollment',
       fn($q) => $q->where('student_id', $student->id)->where('status', EnrollmentStatus::Approved)
   )->where('status', EnrollmentDetailStatus::Confirmed)->pluck('subject_id')->toArray()
   ```
3. Para cada Subject, llama `PrerequisiteValidator::canTake($student, $subject)`
4. Extrae `detailsBySubjectId` del `$draft`:
   ```php
   $draft->loadMissing('details')
   $detailsBySubjectId = $draft->details->whereNotIn('status', ['rejected'])->keyBy('subject_id')
   ```
5. Retorna `Collection` de arrays con la estructura:
   ```php
   ['subject' => $subject, 'prereqs_ok' => bool, 'completed' => bool,
    'recommended_trim' => bool, 'selected_section_id' => ?int, 'sections' => $subject->sections]
   ```
   Solo incluye materias con `$subject->sections->isNotEmpty()`

**Constructor:**
```php
public function __construct(
    private PrerequisiteValidator $validator,
) {}
```

**Criterio de done:**
- La action inyecta `PrerequisiteValidator` via constructor
- Retorna `Collection` correctamente ordenada (por `period_number` ASC)
- Sin N+1 queries (todo via eager-loading)
- Pint pasa sin errores

---

### Task 3 — `EnrollmentCatalogSubjectResource`

**Archivos involucrados:**
- `app/Http/Resources/Enrollment/EnrollmentCatalogSubjectResource.php` (nuevo)

**`toArray()` retorna:**
```php
[
    'id'                  => $subject->id,
    'code'                => $subject->code,
    'name'                => $subject->name,
    'credits'             => $subject->credits_uc,
    'type'                => 'oblig',
    'recommended_trim'    => $this->resource['recommended_trim'],
    'prereqs_ok'          => $this->resource['prereqs_ok'],
    'completed'           => $this->resource['completed'],
    'description'         => $subject->description ?? '',
    'selected_section_id' => $this->resource['selected_section_id'],
    'sections'            => $this->resource['sections']->map(fn(Section $s) => $this->sectionShape($s)),
]
```

**Método privado `sectionShape(Section $section): array`:**
- `professor` → primer schedule con professor → fallback a mainTeacher → fallback a `['id'=>0,'name'=>'Por asignar','initials'=>'··']`
- `initials` → `str_split()` del nombre en palabras, primera letra de cada una, max 2, strtoupper
- `room` → `$section->theoryClassroom?->identifier ?? $section->labClassroom?->identifier ?? 'Por asignar'`
- `modality` → analiza `$section->schedules->pluck('type')`:
  - solo `theory` → `'Teórica'`
  - solo `lab` → `'Laboratorio'`
  - ambos → `'Mixta'`
  - vacío → `'Por definir'`
- `slots` → `$section->schedules->map(fn($s) => ['day' => $s->day_of_week->order() - 1, 'start' => Carbon::parse($s->start_time)->format('H:i'), 'end' => Carbon::parse($s->end_time)->format('H:i')])`
- `enrolled` → `$section->enrolled` (viene del `withCount`)
- `is_full` → `$section->enrolled >= $section->capacity`
- `no_schedule` → `$section->schedules->isEmpty()`

**Nota:** `$this->resource` es el array devuelto por `BuildEnrollmentCatalogAction` — no un Eloquent model. Acceder vía `$this->resource['subject']`, `$this->resource['sections']`, etc.

**Criterio de done:**
- Todos los campos presentes y tipados
- `initials` generadas correctamente (ej: "Marco Salas" → "MS")
- `day` correcto (Lunes=0, Sábado=5)
- `is_full` correcto cuando `enrolled >= capacity`
- Pint pasa sin errores

---

### Task 4 — Refactorizar `EnrollmentController::index()`

**Archivos involucrados:**
- `app/Http/Controllers/Enrollment/EnrollmentController.php`

**Cambios al método `index()`:**
- Acepta `Request $request` (para leer `student_id` query param)
- Extrae estudiante con `private function resolveStudent(User $user, ?int $studentId): ?Student`
  - Si `$user->student` → retorna `$user->student` (ignora `$studentId`)
  - Si `$user->guardian` → valida que `$studentId` pertenezca a `$user->guardian->students()`, aborta 403 si no
  - Si `$user->guardian` sin `$studentId` → retorna el primer estudiante asignado
  - Si ni student ni guardian → abortar 403
- Obtiene período activo: `Period::where('status', PeriodStatus::Active)->first()`
- Si no hay período o no hay pensum → render con catalog vacío
- Auto-crea draft con `Enrollment::firstOrCreate(...)` — solo crea si status draft, no si hay confirmed/approved
  - Si ya existe un Enrollment confirmed/approved en este período → usar ese (read-only)
- Llama `BuildEnrollmentCatalogAction::handle($student, $period, $enrollment)`
- Render Inertia con: `enrollment`, `catalog`, `rules`, `can`
- Añadir método privado `buildRules(?Period $period, ?Student $student): array`

**Criterio de done:**
- `GET /enrollment` retorna Inertia render con las 4 claves: `enrollment`, `catalog`, `rules`, `can`
- `enrollment` contiene el `EnrollmentResource` del draft actual
- `catalog` contiene array de subjects con secciones del período activo
- Guardian sin student_id ve el primer estudiante asignado
- Guardian con student_id inválido recibe 403
- Usuario sin student/guardian recibe 403
- Pint pasa sin errores

---

### Task 5 — Feature tests para `index` actualizado

**Archivos involucrados:**
- `tests/Feature/Enrollment/EnrollmentIndexTest.php` (nuevo)

**Tests a incluir:**
```
test('student sees enrollment index with catalog')
test('student with no active period sees empty catalog')
test('student with no pensum sees empty catalog')
test('guardian sees enrollment for assigned student')
test('guardian without student_id sees first assigned student')
test('guardian with unassigned student_id gets 403')
test('user without student/guardian gets 403')
test('index auto-creates draft enrollment when none exists')
test('index does not create duplicate draft for same period')
test('index returns catalog with prereqs_ok correctly')
test('index returns selected_section_id for existing draft details')
```

**Criterio de done:**
- Todos los tests pasan con `vendor/bin/sail artisan test --compact --filter=EnrollmentIndex`
- Tests usan factories, no fixtures hardcodeados
- RefreshDatabase en todos los tests

---

### Task 6 — Frontend: tipos Backend* + `backendToCatalog()`

**Archivos involucrados:**
- `resources/js/types/enrollment.ts`

**Lo que se añade** (sin eliminar tipos existentes):
- Interfaces: `BackendEnrollmentSlot`, `BackendEnrollmentSection`, `BackendEnrollmentSubject`, `BackendEnrollmentRules`, `BackendEnrollmentDetail`, `BackendEnrollment`
- Función exportada: `backendToCatalog(items: BackendEnrollmentSubject[]): EnrollmentSubject[]`
  - Mapea campo por campo a los tipos UI existentes
  - `credits_uc` → `credits`
  - `prereqs_ok` → `prereqsOk`
  - `recommended_trim` → `recommendedTrim`
  - `no_schedule` → `noSchedule`
  - `is_full`: sección llena → `enrolled = capacity` (la UI solo usa `enrolled < capacity` para mostrar disponibilidad)

**Criterio de done:**
- TypeScript compila sin errores (`vendor/bin/sail npm run build` pasa)
- `backendToCatalog` retorna array de `EnrollmentSubject` tipado correctamente
- No se elimina ningún tipo existente

---

### Task 7 — `useEnrollmentForm.ts`

**Archivos involucrados:**
- `resources/js/composables/enrollment/useEnrollmentForm.ts` (nuevo)

**Firma del composable:**
```typescript
export function useEnrollmentForm(
    enrollment: Ref<BackendEnrollment | null>,
    selections: Ref<EnrollmentSelections>,
)
```

**Expone:**
```typescript
{
    isLoading: Ref<boolean>,
    error: Ref<string | null>,
    clearError(): void,
    addSubject(subjectId: number, sectionId: number, subjectCode: string, sectionIdx: number): Promise<void>,
    removeSubject(detailId: number, subjectCode: string): Promise<void>,
    confirmEnrollment(): Promise<void>,
}
```

**Implementación:**
- Usa `useHttp()` de `@inertiajs/vue3` para todas las llamadas
- Rutas via Wayfinder (`@/routes/enrollment`)
- `addSubject`: si enrollment es null → error; en éxito 201 → `selections.value[subjectCode] = sectionIdx`; en 422 → `error.value = data.error` (NO actualiza selections)
- `removeSubject`: en éxito → `delete selections.value[subjectCode]`; en error → `error.value = ...`
- `confirmEnrollment`: en éxito → `router.reload()`; en 422 → `error.value = data.error`
- `isLoading = true` al inicio de cada operación, `false` al finalizar (success o error)

**Criterio de done:**
- TypeScript compila sin errores
- `router.post/.put/.delete` no aparece en este archivo (solo `useHttp`)
- Sin imports de axios ni fetch crudo
- Rutas vía Wayfinder

---

### Task 8 — `useEnrollmentPermissions.ts`

**Archivos involucrados:**
- `resources/js/composables/enrollment/useEnrollmentPermissions.ts` (nuevo)

**Firma:**
```typescript
export function useEnrollmentPermissions(
    enrollment: Ref<BackendEnrollment | null>,
    can: { confirm: boolean },
)
```

**Expone:**
```typescript
{
    canConfirm: ComputedRef<boolean>,   // can.confirm && enrollment?.status === 'draft'
    canEdit: ComputedRef<boolean>,      // enrollment?.status === 'draft'
    isReadOnly: ComputedRef<boolean>,   // !enrollment || enrollment.status !== 'draft'
}
```

**Criterio de done:**
- `usePage().props.auth` no aparece en este archivo (solo usa los parámetros recibidos)
- TypeScript compila sin errores
- `isReadOnly` es `true` cuando `enrollment === null` o `status !== 'draft'`

---

### Task 9 — Conectar `Index.vue` a props reales

**Archivos involucrados:**
- `resources/js/pages/enrollment/Index.vue`
- `resources/js/composables/enrollment/enrollmentMockData.ts` (eliminar)

**Cambios en `Index.vue`:**

1. Añadir `defineProps<{enrollment: BackendEnrollment|null; catalog: BackendEnrollmentSubject[]; rules: BackendEnrollmentRules; can: {confirm: boolean}}>()`
2. Reemplazar `ENROLLMENT_SUBJECTS` → `computed(() => backendToCatalog(props.catalog))`
3. Reemplazar `ENROLLMENT_RULES` → mapear `props.rules` al shape `EnrollmentRules` (adaptar campos snake_case → camelCase)
4. Reemplazar `ENROLLMENT_INITIAL_SELECTIONS` → derivar de `props.enrollment?.details` (ver design.md)
5. Añadir `useEnrollmentForm(toRef(props, 'enrollment'), selections)` y `useEnrollmentPermissions(toRef(props, 'enrollment'), props.can)`
6. Reemplazar `handleConfirm()` → `await confirmEnrollment()`
7. Conectar `@select` y `@unselect` de `EnrollmentMateriaRow` a `handleSelect()` / `handleUnselect()` que llaman al form composable
8. Pasar `isReadOnly` a `EnrollmentMateriaRow` y `EnrollmentSummaryPanel`
9. Mostrar `error` en `EnrollmentSummaryPanel` (si el componente tiene slot para ello; si no, añadir `v-if="error"` con mensaje de error sobre el panel)
10. Eliminar todos los imports de `enrollmentMockData`

**Criterio de done:**
- `vendor/bin/sail npm run build` pasa sin errores TypeScript
- La página renderiza correctamente con datos reales del servidor (verificar con `php artisan tinker` o test)
- No hay ningún import de `enrollmentMockData` en el codebase
- Seleccionar una sección llama al backend (verificable en logs de Laravel)
- Confirmar inscripción cambia el status a `confirmed` y recarga la página en read-only
- Eliminar materia llama al backend y actualiza el panel de resumen localmente
- Filtros y búsqueda siguen funcionando
- La detección de conflictos de horario sigue funcionando

---

## Notas de ejecución

- El orden de tasks 1→5 (backend) debe completarse antes de 6→9 (frontend)
- Task 5 (tests) puede ejecutarse en paralelo con Tasks 6-8 si hay dos agentes
- Task 9 (Index.vue) requiere que Tasks 6, 7 y 8 estén completos
- Después de cada archivo PHP modificado: `vendor/bin/sail bin pint --dirty --format agent`
- Después de cada tarea frontend: `vendor/bin/sail npm run build` para verificar TypeScript
