# HLZ-39 + HLZ-42 — Dashboard del estudiante: crash de domingo y horario no filtrado

## Problema

**HLZ-42:** `Student\DashboardController::index()` calcula el día de hoy con `strtolower(now()->format('l'))` y lo convierte con `DayOfWeek::from($todayValue)`. El enum `App\Enums\DayOfWeek` solo define `Monday`..`Saturday` — no existe `Sunday`. Cualquier domingo, `DayOfWeek::from('sunday')` lanza un `ValueError` sin try/catch, tumbando con 500 la carga completa del dashboard del estudiante (no solo el horario — también período, inscripción, UC del pensum y representantes, porque todo se resuelve en el mismo método antes del `return`). Confirmado en vivo con tres cuentas distintas.

**HLZ-39:** el widget "Hoy" construye `today_schedules` a partir de `$enrollment->details` (relación sin scope), en vez de `$enrollment->confirmedDetails()` (scope ya existente en `Enrollment.php`, filtra `status = confirmed`). Un estudiante puede ver en su horario materias todavía en `draft` o incluso `rejected`, mezcladas con las `confirmed`.

## Requisitos funcionales

- RF-01: `GET /student/dashboard` un domingo responde 200 (no 500), con `today_schedules: []` y `today_label` indicando que no hay clases.
- RF-02: `GET /student/dashboard` de lunes a sábado sigue funcionando exactamente igual que hoy (regresión) — período, inscripción, UC del pensum, representantes y horario del día se calculan sin cambios de comportamiento.
- RF-03: `today_schedules` solo incluye secciones de `EnrollmentDetail` con `status = confirmed`. Un `EnrollmentDetail` en `draft` o `rejected` para el mismo día nunca aparece en el widget.

## Nota de reconciliación (2026-08-30)

HLZ-42 ya fue corregido de forma independiente en el commit `2a91c45` ("estable"), fuera de este arnés: `DashboardController::index()` (Student y Professor) ya usa `DayOfWeek::tryFrom($todayValue)?->label() ?? 'Domingo'`. RF-01 debe verificarse (no implementarse desde cero) al ejecutar Task 1. HLZ-39 (`confirmedDetails`) sigue sin resolver — es el único cambio de código real pendiente en este feature.

## Alcance

**Incluye:** manejo seguro de `DayOfWeek::tryFrom()` con estado vacío para días sin caso en el enum; filtrar `today_schedules` por `EnrollmentDetail::status = confirmed`.

**Excluye explícitamente:**
- Agregar el caso `Sunday` al enum `DayOfWeek` — confirmado que no hay clases los domingos, no hace falta.
- Cualquier otro widget del dashboard del estudiante fuera del cálculo de "Hoy".
