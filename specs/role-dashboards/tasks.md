# Tasks — Role Dashboards

**Feature:** `07-role-dashboards`

---

## Progreso general

- [ ] Task 1 — Relación `Professor::sections()` + `Professor\DashboardController`
- [ ] Task 2 — `Student\DashboardController`
- [ ] Task 3 — `Guardian\DashboardController`
- [ ] Task 4 — TypeScript types para los tres dashboards
- [ ] Task 5 — `professor/Dashboard.vue`
- [ ] Task 6 — `student/Dashboard.vue`
- [ ] Task 7 — `guardian/Dashboard.vue`
- [ ] Task 8 — Pest feature tests

---

## Detalle de cada task

---

### Task 1 — Relación `Professor::sections()` + `Professor\DashboardController`

**Archivos involucrados:**
- `app/Models/Professor.php` — agregar relación
- `app/Http/Controllers/Professor/DashboardController.php` — reemplazar stub

**Agregar a `Professor`:**

```php
public function sections(): HasMany
{
    return $this->hasMany(Section::class, 'main_teacher_id');
}
```

**`Professor\DashboardController::index()`:**

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $professor = $user->professor;
    $period = Period::where('status', 'active')->first();

    $now = now();
    $todayValue = strtolower($now->format('l'));
    $todayLabel = DayOfWeek::from($todayValue)->label();
    $currentTime = $now->format('H:i:s');

    $sections = $period
        ? Section::where('main_teacher_id', $professor->id)
            ->where('period_id', $period->id)
            ->with([
                'subject',
                'schedules' => fn ($q) => $q->where('day_of_week', $todayValue)
                    ->with('classroom')
                    ->orderBy('start_time'),
                'enrollmentDetails' => fn ($q) => $q->whereIn('status', ['confirmed', 'approved']),
            ])
            ->get()
        : collect();

    $hoursPerWeek = $period
        ? Schedule::where('professor_id', $professor->id)
            ->whereHas('section', fn ($q) => $q->where('period_id', $period->id))
            ->get()
            ->sum(fn ($s) => Carbon::parse($s->end_time)->diffInMinutes(Carbon::parse($s->start_time)) / 60)
        : 0;

    $todaySchedules = $sections->flatMap(fn ($section) =>
        $section->schedules->map(fn ($schedule) => [
            'section_id'      => $section->id,
            'subject_name'    => $section->subject->name,
            'section_code'    => $section->code,
            'classroom_name'  => $schedule->classroom?->name ?? '—',
            'students_count'  => $section->enrollmentDetails->count(),
            'start_time'      => substr($schedule->start_time, 0, 5),
            'end_time'        => substr($schedule->end_time, 0, 5),
            'is_current'      => $currentTime >= $schedule->start_time
                                 && $currentTime <= $schedule->end_time,
        ])
    )->sortBy('start_time')->values();

    return Inertia::render('professor/Dashboard', [
        'period'         => $period ? ['name' => $period->name, 'type' => $period->type] : null,
        'sections_count' => $sections->count(),
        'total_students' => $sections->sum(fn ($s) => $s->enrollmentDetails->count()),
        'hours_per_week' => round($hoursPerWeek, 1),
        'today_label'    => $todayLabel,
        'today_schedules'=> $todaySchedules,
    ]);
}
```

**Imports necesarios:**
```php
use App\Enums\DayOfWeek;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
```

**Criterio de done:**
- `GET /professor/dashboard` retorna 200 con las props correctas
- Sin N+1 (eager loading en todas las relaciones)
- `vendor/bin/sail bin pint --dirty` pasa sin errores

---

### Task 2 — `Student\DashboardController`

**Archivo:** `app/Http/Controllers/Student/DashboardController.php`

**Lógica:**

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $student = $user->student;
    $period = Period::where('status', 'active')->first();

    $now = now();
    $todayValue = strtolower($now->format('l'));
    $todayLabel = DayOfWeek::from($todayValue)->label();
    $currentTime = $now->format('H:i:s');

    $enrollment = $period
        ? $student->enrollments()
            ->where('period_id', $period->id)
            ->with([
                'details.section.subject',
                'details.section.schedules' => fn ($q) => $q->where('day_of_week', $todayValue)
                    ->with('classroom')
                    ->orderBy('start_time'),
            ])
            ->first()
        : null;

    $ucPensum = $student->pensum?->subjects()->sum('credits') ?? 0;

    $todaySchedules = $enrollment
        ? $enrollment->details->flatMap(fn ($detail) =>
            $detail->section->schedules->map(fn ($schedule) => [
                'subject_name'   => $detail->section->subject->name,
                'section_code'   => $detail->section->code,
                'classroom_name' => $schedule->classroom?->name ?? '—',
                'start_time'     => substr($schedule->start_time, 0, 5),
                'end_time'       => substr($schedule->end_time, 0, 5),
                'is_current'     => $currentTime >= $schedule->start_time
                                    && $currentTime <= $schedule->end_time,
            ])
        )->sortBy('start_time')->values()
        : collect();

    return Inertia::render('student/Dashboard', [
        'period'          => $period ? ['name' => $period->name] : null,
        'enrollment'      => $enrollment ? [
            'id'          => $enrollment->id,
            'status'      => $enrollment->status->value,
            'uc_inscritas'=> $enrollment->uc_inscritas,
        ] : null,
        'subjects_count'  => $enrollment?->details->count() ?? 0,
        'uc_pensum'       => $ucPensum,
        'uc_aprobadas'    => 0,
        'today_label'     => $todayLabel,
        'today_schedules' => $todaySchedules,
    ]);
}
```

**Nota:** `$user->student` — verificar que la relación `student()` exista en `User`. Si no, añadirla.

**Criterio de done:**
- `GET /student/dashboard` retorna 200 con props correctas
- Estudiante sin inscripción → `enrollment: null`, `today_schedules: []`
- Pint pasa sin errores

---

### Task 3 — `Guardian\DashboardController`

**Archivo:** `app/Http/Controllers/Guardian/DashboardController.php`

**Lógica:**

```php
public function index(Request $request): Response
{
    $user = $request->user();
    $guardian = $user->guardian;
    $period = Period::where('status', 'active')->first();

    $students = $guardian->students()
        ->with([
            'user:id,name',
            'pensum.subjects:id,pensum_id,credits',
            'enrollments' => fn ($q) => $period
                ? $q->where('period_id', $period->id)
                    ->with('details.section.subject:id,name')
                : $q->whereRaw('false'),
        ])
        ->get()
        ->map(function (Student $student) use ($period) {
            $enrollment = $student->enrollments->first();
            $ucPensum = $student->pensum?->subjects->sum('credits') ?? 0;

            return [
                'id'                 => $student->id,
                'name'               => $student->user->name,
                'educational_level'  => $student->educational_level->value,
                'academic_year'      => $student->academic_year,
                'pensum_name'        => $student->pensum?->name,
                'uc_pensum'          => $ucPensum,
                'uc_aprobadas'       => 0,
                'enrollment_status'  => $enrollment?->status->value,
                'uc_inscritas'       => $enrollment?->uc_inscritas ?? 0,
                'nota_promedio'      => null,
                'inasistencias'      => null,
                'subjects'           => $enrollment
                    ? $enrollment->details->map(fn ($d) => [
                        'id'   => $d->section->subject->id,
                        'name' => $d->section->subject->name,
                    ])->unique('id')->values()
                    : [],
            ];
        });

    return Inertia::render('guardian/Dashboard', [
        'period'   => $period ? ['name' => $period->name] : null,
        'students' => $students,
    ]);
}
```

**Nota:** `$user->guardian` — verificar que la relación exista en `User`.

**Criterio de done:**
- `GET /guardian/dashboard` retorna 200 con props correctas
- Guardian con 2 representados → array de 2 entries
- Pint pasa sin errores

---

### Task 4 — TypeScript types

**Archivos nuevos:**
- `resources/js/types/professor-dashboard.ts`
- `resources/js/types/student-dashboard.ts`
- `resources/js/types/guardian-dashboard.ts`

**`professor-dashboard.ts`:**

```typescript
export interface TodaySchedule {
  section_id: number
  subject_name: string
  section_code: string
  classroom_name: string
  students_count: number
  start_time: string
  end_time: string
  is_current: boolean
}

export interface ProfessorDashboardProps {
  period: { name: string; type: string } | null
  sections_count: number
  total_students: number
  hours_per_week: number
  today_label: string
  today_schedules: TodaySchedule[]
}
```

**`student-dashboard.ts`:**

```typescript
export interface StudentTodaySchedule {
  subject_name: string
  section_code: string
  classroom_name: string
  start_time: string
  end_time: string
  is_current: boolean
}

export interface StudentDashboardProps {
  period: { name: string } | null
  enrollment: { id: number; status: string; uc_inscritas: number } | null
  subjects_count: number
  uc_pensum: number
  uc_aprobadas: number
  today_label: string
  today_schedules: StudentTodaySchedule[]
}
```

**`guardian-dashboard.ts`:**

```typescript
export interface GuardianStudent {
  id: number
  name: string
  educational_level: string
  academic_year: number
  pensum_name: string | null
  uc_pensum: number
  uc_aprobadas: number
  enrollment_status: string | null
  uc_inscritas: number
  nota_promedio: null
  inasistencias: null
  subjects: Array<{ id: number; name: string }>
}

export interface GuardianDashboardProps {
  period: { name: string } | null
  students: GuardianStudent[]
}
```

**Criterio de done:**
- `vendor/bin/sail npm run build` pasa sin errores TypeScript

---

### Task 5 — `professor/Dashboard.vue`

**Archivo:** `resources/js/pages/professor/Dashboard.vue`

**Estructura del template:**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { ProfessorDashboardProps } from '@/types/professor-dashboard'

const props = defineProps<ProfessorDashboardProps>()
const page = usePage()
const userName = computed(() => page.props.auth?.user?.name ?? '')
</script>

<template>
  <div class="dashboard">
    <!-- Encabezado -->
    <div class="dashboard__header">
      <h1>Bienvenido/a, {{ userName }}</h1>
      <p v-if="period">{{ period.name }}</p>
    </div>

    <!-- Métricas -->
    <div class="dashboard__stats">
      <div class="stat-card">
        <span class="stat-card__value">{{ sections_count }}</span>
        <span class="stat-card__label">Secciones activas</span>
      </div>
      <div class="stat-card">
        <span class="stat-card__value">{{ total_students }}</span>
        <span class="stat-card__label">Estudiantes totales</span>
      </div>
      <div class="stat-card">
        <span class="stat-card__value">{{ hours_per_week }}h</span>
        <span class="stat-card__label">Horas / semana</span>
      </div>
    </div>

    <!-- Timeline de hoy -->
    <section class="dashboard__today">
      <h2 class="dashboard__section-title">Hoy — {{ today_label }}</h2>
      <p v-if="!today_schedules.length" class="dashboard__empty">
        No tienes clases hoy.
      </p>
      <div v-else class="timeline">
        <div
          v-for="item in today_schedules"
          :key="item.section_id"
          class="timeline__item"
          :class="{ 'timeline__item--current': item.is_current }"
        >
          <div class="timeline__time">
            {{ item.start_time }} – {{ item.end_time }}
            <span v-if="item.is_current" class="timeline__now">AHORA</span>
          </div>
          <div class="timeline__title">{{ item.subject_name }} — {{ item.section_code }}</div>
          <div class="timeline__meta">{{ item.classroom_name }} · {{ item.students_count }} estudiantes</div>
        </div>
      </div>
    </section>
  </div>
</template>
```

Aplicar estilos con clases Tailwind usando la paleta CACAO:
- `stat-card__value`: terracota, font bold grande
- `timeline__item--current`: borde izquierdo terracota destacado
- `timeline__now`: badge terracota sólido

**Criterio de done:**
- Página carga sin errores TS
- Con datos del seeder: métricas muestran valores reales
- Clase activa muestra badge AHORA si corresponde por hora
- `vendor/bin/sail npm run build` sin errores

---

### Task 6 — `student/Dashboard.vue`

**Archivo:** `resources/js/pages/student/Dashboard.vue`

**Variaciones respecto al del profesor:**
- Métricas: materias · UC inscritas · progreso pensum (`uc_aprobadas / uc_pensum`)
- Si `enrollment === null`: mostrar banner con enlace a `/enrollment` en lugar de timeline
- El tercer stat muestra `{{ uc_aprobadas }}/{{ uc_pensum }} UC` y usa `uc_pensum === 0 ? '—' : ...`

**Banner sin inscripción:**
```vue
<div v-if="!enrollment" class="dashboard__cta-banner">
  <p>No tienes inscripción activa para este período.</p>
  <Link :href="enrollmentIndex.url()">Ir a inscripciones →</Link>
</div>
```

Wayfinder import: `import { index as enrollmentIndex } from '@/routes/enrollment'`

**Criterio de done:**
- Estudiante con inscripción: muestra métricas + timeline
- Estudiante sin inscripción: muestra banner CTA
- Sin clases hoy: estado vacío
- `vendor/bin/sail npm run build` sin errores

---

### Task 7 — `guardian/Dashboard.vue`

**Archivo:** `resources/js/pages/guardian/Dashboard.vue`

**Template:**

```vue
<div v-for="student in students" :key="student.id" class="student-card">
  <!-- Header -->
  <div class="student-card__header">
    <div>
      <h3>{{ student.name }}</h3>
      <p>{{ levelLabel(student.educational_level) }} · Año {{ student.academic_year }}</p>
    </div>
    <span class="enrollment-badge" :class="badgeClass(student.enrollment_status)">
      {{ enrollmentLabel(student.enrollment_status) }}
    </span>
  </div>

  <!-- Mini stats -->
  <div class="student-card__stats">
    <div class="mini-stat"><span>{{ student.uc_inscritas }}</span><small>UC inscritas</small></div>
    <div class="mini-stat"><span>—</span><small>Nota prom.</small></div>
    <div class="mini-stat"><span>—</span><small>Inasistencias</small></div>
    <div class="mini-stat">
      <span>{{ ucPercent(student) }}%</span>
      <small>Pensum</small>
    </div>
  </div>

  <!-- Progress bar -->
  <div class="student-card__progress">
    <div class="progress-bar">
      <div class="progress-bar__fill" :style="{ width: ucPercent(student) + '%' }"></div>
    </div>
    <small>{{ student.uc_aprobadas }} / {{ student.uc_pensum }} UC</small>
  </div>

  <!-- Subjects -->
  <ul class="student-card__subjects">
    <li v-for="subject in student.subjects" :key="subject.id">{{ subject.name }}</li>
  </ul>
</div>
```

Helpers en `<script setup>`:
```typescript
const ucPercent = (s: GuardianStudent) =>
  s.uc_pensum > 0 ? Math.round((s.uc_aprobadas / s.uc_pensum) * 100) : 0

const enrollmentLabel = (status: string | null) =>
  status ? { draft: 'Borrador', confirmed: 'Confirmada', approved: 'Aprobada', rejected: 'Rechazada' }[status] ?? status : 'Sin inscripción'

const badgeClass = (status: string | null) =>
  ({ draft: 'badge--draft', confirmed: 'badge--confirmed', approved: 'badge--approved', rejected: 'badge--rejected' }[status ?? ''] ?? 'badge--neutral')

const levelLabel = (level: string) =>
  level === 'university' ? 'Universitario' : 'Escolar'
```

**Criterio de done:**
- Guardian con 2 representados: 2 cards
- Mini-stats nota e inasistencias muestran —
- Barra de progreso renderiza correctamente
- `vendor/bin/sail npm run build` sin errores

---

### Task 8 — Pest feature tests

**Archivos:**
- `tests/Feature/Professor/DashboardTest.php`
- `tests/Feature/Student/DashboardTest.php`
- `tests/Feature/Guardian/DashboardTest.php`

**Tests para cada portal:**

```
// Profesor
test('professor dashboard returns 200 with required props')
test('professor dashboard includes sections count for active period')
test('professor dashboard returns empty today_schedules when no classes today')
test('professor cannot access student or guardian portal')

// Estudiante
test('student dashboard returns 200 with required props')
test('student dashboard enrollment is null when no active enrollment')
test('student dashboard shows active enrollment data')
test('student cannot access professor or guardian portal')

// Representante
test('guardian dashboard returns 200 with required props')
test('guardian dashboard lists all represented students')
test('guardian dashboard nota_promedio and inasistencias are null')
test('guardian cannot access professor or student portal')
```

**Criterio de done:**
- Todos los tests pasan con `vendor/bin/sail artisan test --compact --filter=DashboardTest`
- RefreshDatabase en todos
- Factories para: Professor, Student, Guardian, Period, Section, Schedule, Enrollment, EnrollmentDetail

---

## Notas de ejecución

- Tasks 1-3 son backend — ejecutar en orden
- Task 4 (types) puede ir en paralelo con Tasks 1-3
- Tasks 5-7 requieren que Tasks 1-4 estén completas (props shapes definidas)
- Task 8 puede desarrollarse con TDD junto a Tasks 1-3
- Después de cada archivo PHP: `vendor/bin/sail bin pint --dirty --format agent`
- Después de cada tarea frontend: `vendor/bin/sail npm run build` para verificar TypeScript
- Verificar que `$user->professor`, `$user->student` y `$user->guardian` sean relaciones existentes en `User` antes de implementar Tasks 1-3
