# QA — Horario del estudiante
**Fecha de definición:** 2026-08-30
**Vista:** `/student/dashboard` (widget "Hoy" actual) + vista semanal nueva (pendiente de implementar)
**Auditor:** QA Manager

---

## Contexto

**No existe hoy una vista de horario dedicada para el estudiante.** Lo único que cubre esta necesidad es un widget "Hoy — clases de hoy" embebido en `student/Dashboard.vue`, alimentado por `Student\DashboardController::index`, que solo trae los `schedules` del día actual (no la semana) y **no reutiliza** el sistema de componentes `resources/js/components/scheduling/` (`WeeklyGrid.vue`, etc.) usado en la vista admin (`specs/qa/academic/schedules.md`).

**Decisiones confirmadas con el humano (2026-08-30):**
1. El widget "Hoy" debe filtrar por `EnrollmentDetail::status = confirmed` — actualmente no filtra por estado en absoluto. Ver `HLZ-39`.
2. **Sí se necesita una vista semanal completa** (tipo `WeeklyGrid`, como en admin) — no alcanza con arreglar solo el widget del día. Queda documentada aquí como feature pendiente de implementar vía el arnés (`specs/{feature}/`), fuera del alcance de este ciclo de solo-documentación.

---

## UCs del widget "Hoy" (dashboard actual)

- **UC-SS01** — [RESUELTO 2026-08-30, feature 17] GET `/student/dashboard` con inscripción confirmada y clases programadas hoy: `today_schedules` lista las secciones correspondientes a `EnrollmentDetail` en estado `confirmed` únicamente. Verificado con `tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php::RF-02` (regresión, día normal) y `::RF-03` (excluye draft/rejected).
- **UC-SS02** — [RESUELTO 2026-08-30 — ver **HLZ-39**, feature 17] `DashboardController::index()` ahora construye `today_schedules` desde `$enrollment->confirmedDetails` (scope `status = confirmed`) en vez de `$enrollment->details`. `details` se preserva solo para `subjects_count` (no debe filtrarse). Verificado con `DashboardScheduleFixTest::RF-03: today_schedules solo incluye EnrollmentDetail confirmado, excluye draft` y `::RF-03: today_schedules excluye EnrollmentDetail rechazado`.
- **UC-SS02b** — [RESUELTO — ver **HLZ-42**, corregido fuera del arnés en commit `2a91c45`, verificado en feature 17] `GET /student/dashboard` cualquier domingo ya no lanza `ValueError`: `DashboardController.php` usa `DayOfWeek::tryFrom($todayValue)?->label() ?? 'Domingo'`. Verificado con `DashboardScheduleFixTest::RF-01: dashboard un domingo responde 200 con today_schedules vacío` (200, `today_schedules: []`, `today_label` presente).
- **UC-SS03** — Sin clases programadas hoy (día actual sin schedules coincidentes): estado vacío "Sin clases programadas hoy", sin error.
- **UC-SS04** — Sin período activo o sin `Enrollment` para el período activo: `today_schedules` vacío, sin error.
- **UC-SS05** — Cada item del widget expone: materia, código de sección, aula, hora inicio, hora fin, `is_current` (si la clase está en curso ahora mismo).
- **UC-SS06** — [GAP] El widget no expone el nombre del profesor, pese a que la relación `Schedule::professor()` existe en el modelo. Evaluar si se agrega al mismo tiempo que el fix de HLZ-39 o se difiere a la vista semanal (UC-SS08).

## UCs de la vista semanal — **PENDIENTE DE IMPLEMENTAR** (feature nueva, fuera de este ciclo)

- **UC-SS07** — [PENDIENTE] Ruta `GET /student/schedule` (nombre a definir) muestra un grid semanal con todas las secciones en `EnrollmentDetail::confirmed` del estudiante para el período activo, reutilizando los componentes de `resources/js/components/scheduling/` (`WeeklyGrid.vue` u otros ya existentes en el módulo admin).
- **UC-SS08** — [PENDIENTE] Cada slot de la vista semanal expone: materia, profesor, aula, día de la semana, hora inicio/fin, tipo de sesión (teórica/laboratorio).
- **UC-SS09** — [PENDIENTE] El estudiante solo puede ver su propio horario — sin parámetro de ruta que permita a un estudiante ver el horario de otro (nada de `{student}` en la URL; se resuelve siempre por `$request->user()->student`, igual que el dashboard actual).
- **UC-SS10** — [PENDIENTE] Enlace de navegación desde `student/Dashboard.vue` hacia la nueva vista semanal.
- **UC-SS11** — [PENDIENTE] Sin período activo o sin enrollment confirmado: estado vacío en la vista semanal, sin error.

---

## Tests existentes (Feature/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| UC-SS03, SS04 | `tests/Feature/Student/DashboardTest.php` | `today_schedules` presente (solo `->has()`, sin validar contenido/filtrado); casos sin período activo y sin enrollment |
| UC-SS05 | `tests/Feature/Student/DashboardTest.php` | Presencia de props, no valida cada campo individualmente |
| **UC-SS01, SS02** | `tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php` | RF-02 (regresión, todo confirmed) + RF-03 x2 (excluye draft, excluye rejected) — cubre el filtro por `confirmedDetails` end-to-end vía request HTTP real (JSON de Inertia), no Dusk: los UCs de `qa.md` de la feature 17 son de solo lectura, sin formulario, por lo que el Feature test HTTP real ya ejerce controller/policy/página completos |
| **UC-SS02b** | `tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php::RF-01` | Domingo → 200, `today_schedules: []`, `today_label` presente. Test de regresión (fix ya aplicado fuera del arnés en `2a91c45`) |
| UC-SS06 | — | sin cobertura (campo ausente) |
| UC-SS07–SS11 | — | no aplica todavía — feature no implementada |

---

## Notas de implementación pendiente

- Ver `HLZ-42` en `specs/qa/backlog.md` — **prioridad CRÍTICA**, crash 500 total del dashboard cualquier domingo, confirmado en vivo. Recomendado arreglar antes que HLZ-39 (misma línea de código).
- Ver `HLZ-39` en `specs/qa/backlog.md` para el detalle técnico del fix de filtrado por status.
- La vista semanal (UC-SS07–SS11) requiere pasar por el arnés (`specs/{feature}/requirements.md`, `design.md`, `tasks.md`) antes de implementarse — no se arrancó en este ciclo, que fue solo de documentación de UCs y backlog.
