# Tasks — Student Schedule View (feature 21)

- [x] Task 1 — Backend: `Student\ScheduleController@index` + ruta `student.schedule.index` + `StudentScheduleResource` con shape completo (semana + vigencia + profesor + aula + materia)
- [x] Task 2 — Frontend: página `student/Schedule.vue` montando `WeeklyGrid` + `ScheduleListView` alternados por CSS (`hidden lg:block` / `lg:hidden`), ambos read-only (`can-update`/`can-delete` en `false`, sin wiring de click)
  - [x] Task 2b — Fix `ScheduleEventCard.vue`/`useScheduleLayout.ts`: el modo `compact` (clases < 60 min) oculta profesor y aula, violando UC-QA-01. Componente compartido con el módulo admin — el fix no puede regresionar `tests/Browser/Academic/ScheduleViewsTest.php` ni otros consumidores.
  - [x] Task 2c — Fix `AppSidebarLayout.vue`: sin breakpoint responsivo, el sidebar (240px fijos) recorta el contenido en viewport < 1024px en toda la app, no solo en esta feature. Agregar colapso/drawer responsivo sin afectar el layout desktop existente.
- [x] Task 3 — `student/Dashboard.vue`: agregar link "Ver horario completo →" hacia `student.schedule.index` vía Wayfinder
- [x] Task 4 — Tests Feature: filtro `confirmed`, filtro de vigencia, estado vacío sin período/enrollment, 403 cross-role (Admin/Profesor/Representante), sin restricción por nivel educativo
- [x] Task 5 — Tests Dusk: grid desktop con datos correctos, lista mobile, estado vacío, link del dashboard, click sin efecto
- [x] Task 6 — Wayfinder regenerado + `vendor/bin/sail bin pint --dirty --format agent`
- [x] Task 7 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/21-student-schedule-view/qa.md` en verde
