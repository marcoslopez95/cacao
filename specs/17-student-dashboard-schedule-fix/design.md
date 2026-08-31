# HLZ-39 + HLZ-42 — Diseño del fix

## Decisión de diseño

Ambos bugs viven en el mismo método (`DashboardController::index()`) y en el mismo bloque de cálculo de horario — se corrigen juntos en un solo pase por el archivo. No se toca el enum `DayOfWeek` (opción descartada: agregar `Sunday` — se descarta porque no hay clases ese día y agregar un caso muerto no resuelve nada, solo maquilla el síntoma).

## Cambios necesarios

### `app/Http/Controllers/Student/DashboardController.php`

```php
$now = now();
$todayValue = strtolower($now->format('l'));
$todayLabel = DayOfWeek::tryFrom($todayValue)?->label() ?? 'Sin clases hoy';
$currentTime = $now->format('H:i:s');

$enrollment = $period
    ? $student->enrollments()
        ->where('period_id', $period->id)
        ->with([
            'confirmedDetails.section.subject',
            'confirmedDetails.section.schedules' => fn ($q) => $q->where('day_of_week', $todayValue)
                ->with('classroom')
                ->orderBy('start_time'),
        ])
        ->first()
    : null;
```

Y en la construcción de `today_schedules`, cambiar `$enrollment->details` por `$enrollment->confirmedDetails`:

```php
$todaySchedules = $enrollment
    ? $enrollment->confirmedDetails->flatMap(fn ($detail) => $detail->section->schedules->map(fn ($schedule) => [
        // ... resto sin cambios
    ]))->sortBy('start_time')->values()
    : collect();
```

**Nota:** `subjects_count` usa `$enrollment?->details->count()` — se mantiene igual (cuenta materias inscritas totales, no solo las confirmadas del día; no es parte del alcance de HLZ-39, que es específicamente sobre el widget "Hoy").

Cuando `$todayValue` es `'sunday'`, `tryFrom()` devuelve `null`, el eager-load de `confirmedDetails.section.schedules` con `where('day_of_week', 'sunday')` simplemente no encuentra coincidencias (no existen horarios con ese valor) — `today_schedules` resulta en un array vacío de forma natural, sin necesidad de un `if` especial.

## Sin cambios en

- `Enrollment::confirmedDetails()` — el scope ya existe y ya filtra `status = confirmed`, solo faltaba usarlo aquí.
- `App\Enums\DayOfWeek` — sin casos nuevos.
- Resto de widgets del dashboard (UC del pensum, representantes, período).
