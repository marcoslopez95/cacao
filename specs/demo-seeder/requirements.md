# Requisitos — Demo Seeder

**Feature:** `03-demo-seeder`
**Plan fuente:** `docs/superpowers/plans/2026-05-21-demo-seeder.md`

---

## Objetivo

Crear un `DemoSeeder` modular que siembre datos académicos venezolanos realistas en la base de datos, sirviendo tanto para demostraciones a stakeholders como para desarrollo local. El seeder debe reflejar siempre el estado actual del sistema y actualizarse con cada nueva feature.

---

## Instituciones demo

| Nivel | Nombre |
|-------|--------|
| Universitario | Universidad Tecnológica CACAO (UTCACAO) |
| Secundaria | Unidad Educativa CACAO |

---

## Requisitos funcionales

### RF-01 — Estructura académica

- THE SYSTEM SHALL crear 3 categorías de carrera: Ingeniería, Ciencias Económicas, Humanidades y Educación.
- THE SYSTEM SHALL crear 5 carreras activas, con código único por carrera.
- THE SYSTEM SHALL crear 1 pensum activo por carrera (`period_type = semester`, `total_periods = 8`).
- THE SYSTEM SHALL crear 8 materias por pensum (5 en semestre 1, 3 en semestre 2) con códigos únicos.
- THE SYSTEM SHALL establecer prelaciones entre materias de semestre 2 y sus prerrequisitos de semestre 1.

### RF-02 — Infraestructura

- THE SYSTEM SHALL crear 2 edificios: Edificio A (Ingeniería) y Edificio B (Ciencias y Humanidades).
- THE SYSTEM SHALL crear 15 aulas con tipo (`theory`/`laboratory`) y capacidad variada.

### RF-03 — Períodos

- THE SYSTEM SHALL crear el período `2025-II` con `status = closed`.
- THE SYSTEM SHALL crear el período `2026-I` con `status = active`.
- THE SYSTEM SHALL crear 2 lapsos por cada período.

### RF-04 — Profesores

- THE SYSTEM SHALL crear 12 profesores con nombres venezolanos y usuarios asociados.
- Emails: `prof01@utcacao.edu.ve` … `prof12@utcacao.edu.ve`, contraseña: `password`.
- Cada usuario debe tener el rol `Profesor` asignado.

### RF-05 — Estudiantes universitarios

- THE SYSTEM SHALL crear 120 estudiantes universitarios distribuidos en 24 por pensum (5 pensums).
- Emails: `est001@utcacao.edu.ve` … `est120@utcacao.edu.ve`, contraseña: `password`.
- Cada estudiante debe tener `current_pensum_id` asignado y `academic_year` (1–4).
- Cada usuario debe tener el rol `Estudiante` asignado.

### RF-06 — Estudiantes de secundaria y representantes

- THE SYSTEM SHALL crear 20 estudiantes de secundaria con `educational_level = secondary`.
- Emails: `sec01@utcacao.edu.ve` … `sec20@utcacao.edu.ve`, contraseña: `password`.
- THE SYSTEM SHALL crear 1 representante por estudiante de secundaria.
- Emails representantes: `rep01@utcacao.edu.ve` … `rep20@utcacao.edu.ve`, contraseña: `password`.
- Cada representante debe tener el rol `Representante` asignado.

### RF-07 — Secciones y horarios

- THE SYSTEM SHALL crear 2 secciones (A y B) por cada materia de semestre 1 del período `2026-I`.
- THE SYSTEM SHALL crear 1 sección (A) por cada materia de semestre 2 del período `2026-I`.
- Cada sección debe tener 2 horarios en días distintos de la semana (pares Lun/Mié o Mar/Jue).
- Los horarios deben tener profesor y aula asignados.

### RF-08 — Inscripciones

- THE SYSTEM SHALL crear inscripciones para 90 estudiantes universitarios:
  - 30 con `status = draft` (4–5 materias cada una)
  - 30 con `status = confirmed` (4–5 materias cada una)
  - 30 con `status = approved` (4–5 materias cada una)
- 30 estudiantes sin inscripción (para demostrar el flujo de inicio).
- Todas las inscripciones deben respetar cupos y pensum del estudiante.

---

## Requisitos no funcionales

### RNF-01 — Idempotencia

- WHEN `DemoSeeder` se ejecuta más de una vez, THE SYSTEM SHALL NOT duplicar ninguna entidad.
- Cada sub-seeder usará `firstOrCreate` o `updateOrCreate` con un campo natural único como clave.

### RNF-02 — Autonomía

- `DemoSeeder` debe ser completamente autónomo: invocar `DatabaseSeeder` como primer paso.
- Se ejecuta con un solo comando: `php artisan db:seed --class=DemoSeeder`.

### RNF-03 — Mantenimiento

- WHEN se implementa una nueva feature en `feature_list.json`, SE DEBE crear o actualizar un sub-seeder en `database/seeders/Demo/` para que el demo refleje la nueva feature.
- Esta task de actualización debe estar explícita en el `tasks.md` de cada feature futura.

### RNF-04 — Tests

- WHEN `DemoSeeder` se ejecuta en tests, THE SYSTEM SHALL verificar conteos mínimos por entidad.
- Un test de idempotencia debe confirmar que una segunda ejecución no duplica datos.
