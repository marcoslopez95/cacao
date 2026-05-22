# Design — Role Dashboards

**Feature:** `07-role-dashboards`

---

## Decisiones de arquitectura

### 1. Patrón de los controllers de dashboard

Los dashboards son vistas de lectura pura — sin FormRequest, sin Action, sin Wrapper.
El controller consulta, transforma y pasa props a Inertia. No hay lógica de negocio: solo queries con eager loading.

```php
// Patrón general de cada DashboardController
public function index(Request $request): Response
{
    $user = $request->user();
    $professor = $user->professor;
    $period = Period::where('status', 'active')->first();

    // queries con eager loading...

    return Inertia::render('professor/Dashboard', [
        // props tipadas
    ]);
}
```

### 2. Relaciones a agregar

El modelo `Professor` no tiene relación `sections()`. Se agrega:

```php
// app/Models/Professor.php
public function sections(): HasMany
{
    return $this->hasMany(Section::class, 'main_teacher_id');
}
```

El modelo `User` necesita relación polimórfica o directa al perfil según el rol. Se accede a través de `$user->professor` (ya existe vía FK directa). Se confirma antes de implementar.

### 3. Props shape por rol

#### Profesor

```typescript
interface ProfessorDashboardProps {
  period: { name: string; type: string } | null
  sections_count: number
  total_students: number
  hours_per_week: number
  today_label: string           // "Lunes", "Martes", etc.
  today_schedules: Array<{
    section_id: number
    subject_name: string
    section_code: string
    classroom_name: string
    students_count: number
    start_time: string          // "08:00"
    end_time: string
    is_current: boolean
  }>
}
```

#### Estudiante

```typescript
interface StudentDashboardProps {
  period: { name: string } | null
  enrollment: {
    id: number
    status: string
    uc_inscritas: number
  } | null
  subjects_count: number
  uc_pensum: number             // UC totales del pensum (0 si no tiene pensum)
  uc_aprobadas: number          // siempre 0 hasta que exista módulo de notas
  today_label: string
  today_schedules: Array<{
    subject_name: string
    section_code: string
    classroom_name: string
    start_time: string
    end_time: string
    is_current: boolean
  }>
}
```

#### Representante

```typescript
interface GuardianDashboardProps {
  period: { name: string } | null
  students: Array<{
    id: number
    name: string
    educational_level: string   // "university" | "school"
    academic_year: number
    pensum_name: string | null
    uc_pensum: number
    uc_aprobadas: number        // siempre 0
    enrollment_status: string | null
    uc_inscritas: number
    nota_promedio: null         // siempre null
    inasistencias: null         // siempre null
    subjects: Array<{
      id: number
      name: string
    }>
  }>
}
```

### 4. Cálculo de "AHORA" y "Hoy" en el backend

```php
$now = now();
$todayValue = strtolower($now->format('l')); // 'monday', 'tuesday', etc.
$currentTime = $now->format('H:i:s');

// Filtrar schedules de hoy
$todaySchedules = $schedules->filter(fn($s) => $s->day_of_week->value === $todayValue);

// is_current por schedule
$isCurrent = $schedule->day_of_week->value === $todayValue
    && $currentTime >= $schedule->start_time
    && $currentTime <= $schedule->end_time;

// Label en español
$todayLabel = DayOfWeek::from($todayValue)->label(); // "Lunes"
```

### 5. Layout Vue — Profesor y Estudiante (patrón compartido)

```
┌─────────────────────────────────────────┐
│ Bienvenido/a, {nombre}                  │
│ Período: {period.name}                  │
├──────────┬──────────┬────────────────────┤
│ N        │ N        │ N                  │
│ Secciones│ Estudiant│ Horas/sem          │
├─────────────────────────────────────────┤
│ Hoy — {today_label}                     │
│ ┌──────────────────────────────────────┐│
│ │ 07:00 – 08:30  Cálculo I – Sec A    ││
│ │ 10:00 – 11:30  Álgebra Lin. ★AHORA ││
│ │ 14:00 – 15:30  Cálculo II            ││
│ └──────────────────────────────────────┘│
└─────────────────────────────────────────┘
```

Las métricas del estudiante son: materias · UC inscritas · progreso pensum (UC aprobadas / UC totales).

### 6. Layout Vue — Representante

```
┌──────────────────────────────────────────────┐
│ Ana Halvorson                  [Aprobada] ✓  │
│ 4to año · Ing. en Sistemas                    │
│ ┌──────┬──────┬──────┬──────────────────────┐│
│ │  16  │  —   │  —   │  34%                 ││
│ │  UC  │ Nota │ Inas │  Pensum              ││
│ └──────┴──────┴──────┴──────────────────────┘│
│ [=====>                    ] 62/180 UC        │
│ • Cálculo III                                 │
│ • Física II                                   │
│ • Redes I                                     │
└──────────────────────────────────────────────┘
```

### 7. Estado vacío y casos edge

| Caso | Portal | Tratamiento |
|---|---|---|
| Sin período activo | Todos | Banner informativo, sin métricas |
| Sin secciones en el período | Profesor | Métricas en 0, "No tienes secciones este período" |
| Sin inscripción activa | Estudiante | Banner CTA a `/enrollment` en lugar del timeline |
| Sin clases hoy | Profesor / Estudiante | "No tienes clases hoy" con ícono |
| Guardian sin representados | Representante | Estado vacío |

---

## Archivos involucrados

**Modificados — Backend:**
- `app/Models/Professor.php` — agregar `sections(): HasMany`
- `app/Http/Controllers/Professor/DashboardController.php`
- `app/Http/Controllers/Student/DashboardController.php`
- `app/Http/Controllers/Guardian/DashboardController.php`

**Nuevos — Frontend:**
- `resources/js/types/professor-dashboard.ts`
- `resources/js/types/student-dashboard.ts`
- `resources/js/types/guardian-dashboard.ts`

**Modificados — Frontend:**
- `resources/js/pages/professor/Dashboard.vue`
- `resources/js/pages/student/Dashboard.vue`
- `resources/js/pages/guardian/Dashboard.vue`

**Tests:**
- `tests/Feature/Professor/DashboardTest.php`
- `tests/Feature/Student/DashboardTest.php`
- `tests/Feature/Guardian/DashboardTest.php`
