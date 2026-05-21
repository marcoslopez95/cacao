# Diseño — Demo Seeder

**Feature:** `03-demo-seeder`
**Spec completo:** `docs/superpowers/specs/2026-05-21-demo-seeder-design.md`
**Plan de implementación:** `docs/superpowers/plans/2026-05-21-demo-seeder.md`

---

## Arquitectura

### Patrón: Orquestador + sub-seeders por dominio

```
php artisan db:seed --class=DemoSeeder
    ↓
DemoSeeder (orquestador)
    → DatabaseSeeder            (roles, permisos, 4 usuarios base)
    → DemoAcademicSeeder        (categorías, carreras, pensums, materias, prelaciones)
    → DemoInfrastructureSeeder  (edificios, aulas)
    → DemoPeriodSeeder          (períodos, lapsos)
    → DemoProfessorsSeeder      (12 profesores + usuarios)
    → DemoStudentsSeeder        (120 uni + 20 sec + 20 representantes)
    → DemoSectionsSeeder        (secciones 2026-I + horarios)
    → DemoEnrollmentSeeder      (inscripciones con estados variados)
```

### Estructura de archivos

```
database/seeders/
├── DemoSeeder.php
└── Demo/
    ├── DemoAcademicSeeder.php
    ├── DemoInfrastructureSeeder.php
    ├── DemoPeriodSeeder.php
    ├── DemoProfessorsSeeder.php
    ├── DemoStudentsSeeder.php
    ├── DemoSectionsSeeder.php
    └── DemoEnrollmentSeeder.php
```

---

## Datos del demo

### Académico (DemoAcademicSeeder)

| Entidad | Detalle |
|---------|---------|
| Categorías | Ingeniería · Ciencias Económicas · Humanidades y Educación |
| Carreras | INF (Ing. Informática) · CIV (Ing. Civil) · CON (Contaduría) · ADM (Administración) · EDU (Educación Mat.) |
| Pensums | 1 por carrera — Plan de Estudios 2020, 8 semestres |
| Materias | 8 por pensum = 40 total — códigos `XXX-101…XXX-203` |
| Prelaciones | Semestre 2 requiere aprobar semestre 1 (cadenas reales: Cálculo I→II, etc.) |

### Infraestructura (DemoInfrastructureSeeder)

| Entidad | Detalle |
|---------|---------|
| Edificios | Edificio A (Ingeniería) · Edificio B (Ciencias y Humanidades) |
| Aulas | 15 — mix de teóricas (A-101…B-202) y laboratorios (A-Lab1, B-Lab1…) |

### Períodos (DemoPeriodSeeder)

| Período | Tipo | Estado | Fechas |
|---------|------|--------|--------|
| 2025-II | Semestre | Cerrado | 2025-07-14 → 2026-01-16 |
| 2026-I  | Semestre | Activo  | 2026-01-19 → 2026-07-17 |

Cada período tiene 2 lapsos.

### Personas

| Entidad | Cantidad | Emails | Password |
|---------|----------|--------|----------|
| Profesores | 12 | prof01–prof12@utcacao.edu.ve | password |
| Estudiantes universitarios | 120 | est001–est120@utcacao.edu.ve | password |
| Estudiantes secundaria | 20 | sec01–sec20@utcacao.edu.ve | password |
| Representantes | 20 | rep01–rep20@utcacao.edu.ve | password |

### Secciones y horarios (período 2026-I)

- Semestre 1: 2 secciones (A+B) por materia = 50 secciones
- Semestre 2: 1 sección (A) por materia = 15 secciones
- Total: ~65 secciones + ~130 horarios (2 por sección)

### Inscripciones

| Estado | Cantidad | Detalles por inscripción |
|--------|----------|--------------------------|
| Draft | 30 | 4 materias semestre 1 |
| Confirmed | 30 | 4 materias semestre 1 |
| Approved | 30 | 4 materias semestre 1 |
| Sin inscripción | 30 | Para demo del flujo de inicio |

---

## Estrategia de idempotencia

| Entidad | Clave única para `firstOrCreate` |
|---------|----------------------------------|
| CareerCategory | `name` |
| Career | `code` |
| Pensum | `career_id + name` |
| Subject | `code` |
| Building | `name` |
| Classroom | `identifier` |
| Period | `name` |
| Lapse | `period_id + number` |
| User | `email` |
| Professor | `user_id` |
| Student | `user_id` |
| Guardian | `user_id` |
| Section | `period_id + subject_id + code` |
| Schedule | `section_id + day_of_week` |
| Enrollment | `student_id + period_id` |
| EnrollmentDetail | `enrollment_id + subject_id` |

---

## Tests

Archivo: `tests/Feature/DemoSeederTest.php`

- 7 tests de conteo mínimo (uno por dominio)
- 1 test de idempotencia (ejecutar dos veces, verificar que los conteos no cambian)
