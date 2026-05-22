# Requirements — Role Dashboards

**Feature:** `07-role-dashboards`

---

## Problema

Los dashboards de los portales (Profesor, Estudiante, Representante) son páginas vacías — solo saludo + texto placeholder. No muestran datos reales ni son útiles en el día a día.

---

## Requisitos funcionales

### RF-01 Dashboard del Profesor (`/professor/dashboard`)

- Mostrar métricas del período activo:
  - Número de secciones donde el profesor es `main_teacher_id`
  - Total de estudiantes en esas secciones (conteo de `enrollment_details` con status `confirmed` o `approved`)
  - Horas/semana: suma de duraciones de todos sus schedules únicos del período
- Mostrar agenda del día actual ("Hoy — Lunes"):
  - Lista de clases del día filtradas por `day_of_week = hoy`, ordenadas por `start_time`
  - Cada clase: materia, código de sección, aula, cantidad de estudiantes, hora inicio/fin
  - Indicador **AHORA** en la clase cuya ventana horaria cubre la hora actual
- Si no hay período activo: mensaje informativo
- Si no hay clases hoy: estado vacío elegante

### RF-02 Dashboard del Estudiante (`/student/dashboard`)

- Mostrar métricas del período activo:
  - Cantidad de materias inscritas (enrollment_details del período)
  - UC inscritas totales
  - Progreso en el pensum: UC aprobadas (0 por ahora) / UC totales del pensum
- Mostrar agenda del día actual:
  - Clases de hoy derivadas de los schedules de las secciones inscritas
  - Cada clase: materia, código de sección, aula, hora inicio/fin
  - Indicador **AHORA** en la clase activa por hora
- Si no tiene inscripción activa en el período: banner con CTA a `/enrollment`
- Si no hay clases hoy: estado vacío elegante

### RF-03 Dashboard del Representante (`/guardian/dashboard`)

- Una card por estudiante representado con:
  - Nombre completo, nivel educativo, año académico
  - Badge con el estado de la inscripción del período activo (o "Sin inscripción")
  - 4 mini-stats: UC inscritas · Nota promedio (`null`) · Inasistencias (`null`) · % pensum
  - Barra de progreso del pensum (UC aprobadas 0 / UC totales)
  - Lista de materias inscritas en el período activo
- Los campos `nota_promedio` e `inasistencias` se pasan siempre como `null` — los llenará el módulo de evaluaciones y asistencia cuando estén implementados
- Si el estudiante no tiene inscripción en el período: la card lo indica claramente

### RF-04 Cálculo de "AHORA" y "Hoy"

- **"AHORA"**: calculado en el backend — `is_current: bool` en cada schedule entry
  - `true` si `day_of_week = hoy` Y `start_time ≤ now() ≤ end_time`
- **"Hoy"**: `DayOfWeek::from(strtolower(now()->format('l')))→label()` — retorna "Lunes", "Martes", etc.
- Ambos se calculan en el controller y se pasan como props — nunca en el frontend

---

## Requisitos no funcionales

- Controllers solo consultan y pasan datos — sin lógica de negocio inline
- Sin N+1: eager loading en todas las relaciones usadas
- Props tipadas en TypeScript — interfaces en `types/`
- Wayfinder para todas las rutas frontend
- Sin breaking changes al layout existente

---

## Fuera de scope

- Nota promedio: viene del módulo de evaluaciones (futuro)
- Inasistencias: viene del módulo de asistencia (futuro)
- "En clase ahora" para el representante: viene del módulo de asistencia (futuro)
- Horario semanal completo (grilla L-V): feature separada
- Detalle de materia con actividades/notas: feature separada
