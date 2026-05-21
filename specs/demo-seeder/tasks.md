# Tasks — Demo Seeder

**Feature:** `03-demo-seeder`
**Plan fuente:** `docs/superpowers/plans/2026-05-21-demo-seeder.md`

---

## Progreso general

- [ ] Task 1 — Scaffold: DemoSeeder orquestador + stubs de sub-seeders
- [ ] Task 2 — DemoAcademicSeeder: categorías, carreras, pensums, materias, prelaciones
- [ ] Task 3 — DemoInfrastructureSeeder: edificios y aulas
- [ ] Task 4 — DemoPeriodSeeder: períodos y lapsos
- [ ] Task 5 — DemoProfessorsSeeder: 12 profesores con usuarios
- [ ] Task 6 — DemoStudentsSeeder: 120 universitarios + 20 secundaria + 20 representantes
- [ ] Task 7 — DemoSectionsSeeder: secciones 2026-I + horarios
- [ ] Task 8 — DemoEnrollmentSeeder: inscripciones con estados variados
- [ ] Task 9 — DemoSeederTest completo + idempotencia + feature completada

---

## Detalle de cada task

---

### Task 1 — Scaffold: DemoSeeder orquestador + stubs

**Archivos involucrados:**
- `feature_list.json` (agregar `03-demo-seeder`)
- `database/seeders/DemoSeeder.php` (nuevo)
- `database/seeders/Demo/DemoAcademicSeeder.php` (stub)
- `database/seeders/Demo/DemoInfrastructureSeeder.php` (stub)
- `database/seeders/Demo/DemoPeriodSeeder.php` (stub)
- `database/seeders/Demo/DemoProfessorsSeeder.php` (stub)
- `database/seeders/Demo/DemoStudentsSeeder.php` (stub)
- `database/seeders/Demo/DemoSectionsSeeder.php` (stub)
- `database/seeders/Demo/DemoEnrollmentSeeder.php` (stub)

**Criterio de done:**
- `DemoSeeder` llama a `DatabaseSeeder` + los 7 sub-seeders en orden
- `php artisan db:seed --class=DemoSeeder` corre sin errores (no crea data aún)
- Todos los stubs tienen namespace `Database\Seeders\Demo` y método `run(): void` vacío

---

### Task 2 — DemoAcademicSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoAcademicSeeder.php`
- `tests/Feature/DemoSeederTest.php` (assertions académicas)

**Criterio de done:**
- 3 categorías creadas: Ingeniería · Ciencias Económicas · Humanidades y Educación
- 5 carreras con códigos INF / CIV / CON / ADM / EDU
- 5 pensums activos (`is_active = true`, `period_type = semester`, `total_periods = 8`)
- 40 materias con códigos únicos (`INF-101`…`EDU-203`), `period_number` correcto (1 ó 2)
- ~15 prelaciones en tabla `subject_prerequisites`
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds academic structure"`
- Pint sin errores

---

### Task 3 — DemoInfrastructureSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoInfrastructureSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- 2 edificios: Edificio A — Ingeniería · Edificio B — Ciencias y Humanidades
- 15 aulas con `identifier` único, tipo (`theory`/`laboratory`) y capacidad
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds infrastructure"`
- Pint sin errores

---

### Task 4 — DemoPeriodSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoPeriodSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- Período `2025-II` con `status = closed`
- Período `2026-I` con `status = active`
- 2 lapsos por período (4 total)
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds periods"`
- `Period::where('status', 'active')->first()->name === '2026-I'`
- Pint sin errores

---

### Task 5 — DemoProfessorsSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoProfessorsSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- 12 usuarios con emails `prof01@utcacao.edu.ve` … `prof12@utcacao.edu.ve`
- Cada usuario tiene rol `Profesor` asignado
- 12 registros en tabla `professors` vinculados a esos usuarios
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds professors"`
- Pint sin errores

---

### Task 6 — DemoStudentsSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoStudentsSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- 120 estudiantes universitarios con emails `est001@utcacao.edu.ve` … `est120@utcacao.edu.ve`
- Cada estudiante universitario tiene `current_pensum_id` y `academic_year` (1–4)
- 20 estudiantes de secundaria con emails `sec01@utcacao.edu.ve` … `sec20@utcacao.edu.ve`
- 20 representantes con emails `rep01@utcacao.edu.ve` … `rep20@utcacao.edu.ve`, rol `Representante`
- Cada estudiante de secundaria tiene `guardian_id` no nulo
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds students"`
- Pint sin errores

---

### Task 7 — DemoSectionsSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoSectionsSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- ≥ 60 secciones en período `2026-I` (2 por materia sem-1, 1 por materia sem-2)
- Cada sección tiene `type = university`, `theory_classroom_id` asignado, `capacity = 30`
- ≥ 120 horarios — 2 por sección (par de días Lun/Mié ó Mar/Jue), con `professor_id` y `classroom_id`
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds sections and schedules"`
- Pint sin errores

---

### Task 8 — DemoEnrollmentSeeder

**Archivos involucrados:**
- `database/seeders/Demo/DemoEnrollmentSeeder.php`
- `tests/Feature/DemoSeederTest.php`

**Criterio de done:**
- ≥ 25 inscripciones con `status = draft`
- ≥ 25 inscripciones con `status = confirmed`
- ≥ 25 inscripciones con `status = approved`
- ≥ 270 `EnrollmentDetail` en total (≥ 3 por inscripción)
- Cada `EnrollmentDetail` tiene `enrollment_id`, `subject_id`, `section_id` válidos
- Test pasa: `vendor/bin/sail artisan test --compact --filter="seeds enrollments"`
- Pint sin errores

---

### Task 9 — DemoSeederTest completo + idempotencia + feature completada

**Archivos involucrados:**
- `tests/Feature/DemoSeederTest.php` (versión final con todos los tests)
- `feature_list.json` (marcar `03-demo-seeder` como `completed`)
- `progress/current.md` (actualizar estado)

**Criterio de done:**
- 8 tests en `DemoSeederTest`: 7 de conteo + 1 de idempotencia
- Suite completa pasa: `vendor/bin/sail artisan test --compact --filter=DemoSeeder`
- `php artisan migrate:fresh && php artisan db:seed --class=DemoSeeder` corre sin errores
- `est001@utcacao.edu.ve` puede iniciar sesión y ver el catálogo de inscripciones en `/enrollment`
- `feature_list.json` tiene `"status": "completed"` para `03-demo-seeder`
- Pint sin errores en todos los archivos modificados
