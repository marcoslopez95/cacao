# Student Schedule View — Requerimientos

## Problema / Motivación

El widget "Hoy" del dashboard del estudiante (`resources/js/pages/student/Dashboard.vue`, ya corregido en HLZ-39/feature 17) solo muestra las clases del día actual. El estudiante no tiene ninguna vista de su horario semanal completo — a diferencia del admin/profesor, que ya cuentan con `resources/js/components/scheduling/WeeklyGrid.vue` y `ScheduleListView.vue` en sus propios módulos.

## Alcance

**Incluye:**
- Backend: nuevo controller `app/Http/Controllers/Student/ScheduleController.php` (método `index()`), nueva ruta `GET student/schedule` → nombre `student.schedule.index` (dentro del grupo `role:Estudiante` ya existente en `routes/web.php`, junto a `student.dashboard` y `student.grades.index`), nuevo Resource (`StudentScheduleResource`) con el shape `Schedule[]` que ya consumen `WeeklyGrid`/`ScheduleListView`.
- Frontend: nueva página `resources/js/pages/student/Schedule.vue` que monta `WeeklyGrid` (envuelto en `hidden lg:block`) y `ScheduleListView` (envuelto en `lg:hidden`), ambos sin modificar, en modo puramente de lectura.
- `resources/js/pages/student/Dashboard.vue`: agregar un link "Ver horario completo →" hacia `student.schedule.index` vía Wayfinder.
- Regenerar Wayfinder tras agregar la ruta.

**Excluye explícitamente:**
- Cualquier interacción al hacer click sobre un evento (sin popover, sin navegación).
- Selector de período histórico — solo el período activo (mismo criterio que el widget "Hoy" actual).
- Agregar el nombre del profesor al widget "Hoy" del dashboard (es UC-SS06, un GAP documentado aparte en `specs/qa/academic/student-schedule.md`, no se toca en este feature).
- Cualquier modificación a `WeeklyGrid.vue` o `ScheduleListView.vue` — se usan tal cual, sin cambios.
- Cualquier vista de horario para el representante (fuera de alcance, no aplica).

## Actores y permisos

| Actor | Acción permitida |
|-------|-------------------|
| Estudiante (cualquier `educational_level`: primary, secondary, university) | Ver su propio horario semanal completo, sin ninguna restricción de nivel |
| Admin / Profesor / Representante | 403 (fuera de `role:Estudiante`) |

## Reglas de negocio

1. Solo se muestran secciones cuyo `EnrollmentDetail` esté en estado `confirmed` (no `draft` ni `rejected`).
2. Filtro de vigencia: `valid_from <= hoy` y (`valid_until` nulo o `valid_until >= hoy`) — un horario vencido o aún no vigente hoy no debe aparecer.
3. El estudiante SIEMPRE se resuelve vía `$request->user()->student` — sin parámetro `{student}` en la URL, igual que el dashboard actual (cero superficie de IDOR).
4. Sin período activo, o sin `Enrollment` confirmado para el período activo: la vista muestra un estado vacío, sin error (mismo criterio de UX que el widget "Hoy").
5. Sin restricción por nivel educativo — a diferencia de la restricción de auto-inscripción (HLZ-38, feature 16), que es un caso de negocio completamente distinto (esto es solo lectura de horario, no inscripción).
6. La vista es puramente de lectura — sin ningún tipo de interacción al hacer click sobre un evento.

## Shape de datos requerido (`Schedule[]`)

Confirmado por auditoría de código (`resources/js/types/scheduling.ts:178-193`), consumido tal cual por `WeeklyGrid`/`ScheduleListView`:

```ts
{
  id: number
  section: { id, code, type: 'university'|'school' }
  professor: { id, user: { name } }
  classroom: { id, identifier }
  subject: { id, name, code }
  career: { id, name } | null
  dayOfWeek: 'monday'|'tuesday'|'wednesday'|'thursday'|'friday'|'saturday'
  dayLabel: string
  startTime: string   // "HH:MM"
  endTime: string     // "HH:MM"
  type: 'theory' | 'lab'
  typeLabel: string
  validFrom: string
  validUntil: string | null
}
```

## Casos de error y comportamiento esperado

- Estudiante sin período activo o sin `Enrollment` confirmado para el período activo → estado vacío, HTTP 200, sin error.
- Admin, Profesor o Representante accediendo a `/student/schedule` → 403 (fuera del grupo `role:Estudiante`).
- Sección con `EnrollmentDetail` en `draft` o `rejected` → no aparece en el horario.
- `Schedule` con `valid_until` vencido, o `valid_from` futuro → no aparece en el horario.
- Click sobre cualquier evento del horario (grid o lista) → sin efecto (sin navegación, sin popover).
