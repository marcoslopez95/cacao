# HLZ-43 (ampliación) + HLZ-47 + HLZ-48 — Follow-up de Ronda 3 de QA

## Problema

Tres hallazgos independientes de `specs/qa/backlog.md`, detectados o ampliados en la Ronda 3 de QA (2026-09-13), agrupados en un solo feature por ser correcciones puntuales de bajo alcance:

**HLZ-43 (ampliación):** `EnrollmentController::buildRules()` línea 209 arma `$periodLabel = $student?->academic_year ? "{$student->academic_year}er trimestre" : ''` **sin ninguna condición sobre `$period->type`** — esta línea corre siempre. Solo se pisa cuando `$period?->type === PeriodType::Year` (resuelve el `Lapse` vigente). Para `PeriodType::Semester` (estudiantes universitarios con `academic_year` poblado) el label queda incorrectamente en "Xer trimestre", aunque su período real sea semestral. El fix original de la feature `16-enrollment-level-guard-and-period-fix` resolvió la selección del `Period` correcto por nivel, pero no condicionó este label específico. El mismo síntoma se repite en el copy de ayuda hardcodeado "este trimestre" en `resources/js/pages/enrollment/Index.vue` (línea ~152).

**HLZ-47:** `app/Http/Controllers/Settings/ProfileController.php::edit()` no pasa `user` como prop explícita de Inertia — se apoya enteramente en el dato compartido `auth.user` de `HandleInertiaRequests::share()`. `resources/js/pages/settings/Profile.vue` línea 24 lee `const user = computed(() => page.props.auth.user)` y lo bindea con `:default-value` a los inputs de Nombre y Correo dentro de un `<Form>` de Inertia v2 — patrón de uncontrolled input. Reproducido en vivo: ambos campos cargan vacíos, en desktop y mobile. Este patrón (`usePage().props.auth` dentro de una página) viola la convención propia del proyecto: *"usePage().props.auth solo dentro de composables de permisos — nunca en páginas"* (CLAUDE.md). El componente equivalente `resources/js/pages/teams/Edit.vue` usa el mismo binding `:default-value` dentro de `<Form>`, pero recibe el dato como prop tipada directa (`defineProps`) — y ahí no hay bug reportado.

**HLZ-48:** `professors.weekly_hour_limit` (columna `unsignedSmallInteger`, default 20) es violado por 8 de 18 profesores demo con secciones reales (5 más están exactamente en el límite, sin margen). La causa es que `database/seeders/Demo/DemoProfessorsSeeder.php` fija `weekly_hour_limit => 20` para todos los profesores, mientras `database/seeders/Demo/DemoSectionsSeeder.php` crea los `Schedule` de cada sección escribiendo directo a DB — sin pasar por `StoreScheduleRequest`/`ScheduleConflictService::professorWeeklyHoursExceeded()`, que sí es la validación correcta usada en producción. El resultado bloquea cualquier alta o edición real de horario en el admin: incluso acortar un bloque existente es rechazado, porque la validación compara contra el total horario resultante (correcto), y ese total ya nace por encima del límite.

## Requisitos funcionales

- RF-01: `buildRules()` resuelve `$periodLabel` según `$period->type`: para `Semester`, usa el nombre real del período (nunca el string "Xer trimestre"); para `Year`, mantiene la resolución actual del `Lapse` vigente por fecha.
- RF-02: el copy de ayuda en `enrollment/Index.vue` dedicado al período no repite el texto hardcodeado "este trimestre" cuando el período no es de tipo `Year`.
- RF-03: `GET /settings/profile` devuelve `user` (nombre, correo) como prop explícita y tipada del controller — no como dependencia del shared data de Inertia.
- RF-04: `Profile.vue` recibe `user` vía `defineProps` y ya no lee `usePage().props.auth.user` en el cuerpo de la página.
- RF-05: tras `migrate:fresh --seed`, ningún profesor demo con secciones reales queda con carga horaria semanal por encima de su propio `weekly_hour_limit`.

## Alcance

**Incluye:**
- Condicionar el label de período por `$period->type` en `EnrollmentController::buildRules()`.
- Eliminar el copy hardcodeado "este trimestre" en `enrollment/Index.vue`, usando el label resuelto por backend.
- `ProfileController::edit()` pasa `user` como prop tipada; `Profile.vue` la recibe con `defineProps`.
- Ajustar `weekly_hour_limit` de los profesores demo afectados en el seeder, a un valor que cubra su carga horaria real sembrada.

**Excluye explícitamente:**
- Cambios de comportamiento en `ScheduleConflictService::professorWeeklyHoursExceeded()` — la validación de negocio ya es correcta, no se toca.
- Cualquier otra sección de `/settings` (password, 2FA, etc.).
- El enum `PeriodType::Trimester` — existe en el enum pero no se usa en ningún punto del código actual; no se activa ni se elimina en este feature.
- Reducir o rediseñar la carga horaria sembrada por `DemoSectionsSeeder` — se prefiere subir el límite del profesor antes que tocar secciones/horarios ya usados por QA de otras features (ver `design.md`).

## Actores y permisos

| Actor | Acción permitida |
|-------|-------------------|
| Estudiante o representante (cualquier nivel) | Ve el label de período correcto en `/enrollment` |
| Cualquier usuario autenticado | Ve `/settings/profile` prellenado con su nombre y correo reales |
| Admin | Sin cambio de permisos — solo cambia el dato sembrado por el seeder demo |

## Casos de error / comportamiento esperado

- Un estudiante `secondary`/`primary` sigue viendo el nombre del `Lapse` vigente en el label de período (regresión, no debe romperse por este fix).
- Un usuario recién creado (sin editar su perfil todavía) sigue viendo su nombre/correo reales en `/settings/profile` — no hay estado "vacío" legítimo para un usuario autenticado.
- El badge "Correo verificado." en `/settings/profile` (visible hoy porque los seeders fijan `email_verified_at`) no debe desaparecer por este fix — `ProfileUserResource` incluye `email_verified_at` explícitamente (agregado 2026-09-13 tras bloqueo del tester en modo `pre`, ver `design.md`).
- `migrate:fresh --seed` no debe fallar ni generar warnings por el ajuste de `weekly_hour_limit` — es un cambio de dato, no de validación.
