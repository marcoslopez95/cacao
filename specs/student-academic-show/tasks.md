# Tasks — student-academic-show

**Feature:** `student-academic-show`
**Scope:** Full-stack — wiring frontend (Index) + backend show + Resource + página Vue + tests
**Depends on:** ninguno (independiente)

---

## Tasks

- [x] T01 — `StudentListResource.php`: agregar campo `'user_id' => $this->user_id`
- [x] T02 — `resources/js/types/student.ts`: agregar `user_id: number` a `StudentListItem`
- [x] T03 — `resources/js/pages/admin/Students/Index.vue`: importar `edit as editUser` desde `@/actions/App/Http/Controllers/Security/UserController` e importar `show as showStudent` desde Wayfinder (una vez generado en T09); cambiar los botones ojo y lápiz en los cuatro layouts (`v-all`, `v-primary`, `v-secondary`, `v-university`) para usar `<Link :href="showStudent({ student: s.id }).url">` y `<Link :href="editUser({ user: s.user_id }).url">` respectivamente
- [x] T04 — `routes/web.php`: agregar `Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');` dentro del grupo `academic.` (antes de la línea del index)
- [x] T05 — `app/Http/Resources/Academic/StudentShowResource.php`: crear el Resource con todos los campos del Nivel 2 (identidad, carrera/pensum, datos académicos, inscripción activa, representantes, historial de inscripciones, secciones actuales con horario)
- [x] T06 — `app/Http/Controllers/Academic/StudentController.php`: agregar método `show(Student $student): Response` con eager loading de todas las relaciones necesarias y `Inertia::render('admin/Students/Show', ['student' => (new StudentShowResource($student))->resolve()])`
- [x] T07 — `resources/js/types/studentShow.ts`: crear interfaces TypeScript que sean mirror exacto del `StudentShowResource` (incluyendo `ActiveEnrollment`, `GuardianSummary`, `EnrollmentHistoryItem`, `CurrentSection`, `StudentShowData`)
- [x] T08 — `resources/js/pages/admin/Students/Show.vue`: crear la página con secciones verticales: header (nombre, botones Volver + Editar), identidad, carrera/pensum, inscripción activa, representantes (condicional por nivel), historial de inscripciones, notas/promedio acumulado, secciones con horario
- [x] T09 — `vendor/bin/sail artisan wayfinder:generate`: regenerar los archivos Wayfinder para exponer la nueva ruta `academic.students.show`
- [x] T10 — `tests/Feature/Academic/Acceptance/StudentShowAcceptanceTest.php`: 8 acceptance tests cubren UC-01 y UC-02 completamente
- [x] T11 — `vendor/bin/sail artisan test --compact tests/Feature/Academic/` — 99 tests pasan (todos los Academic)
- [x] T12 — `vendor/bin/sail bin pint --dirty --format agent` — pass (no dirty files)

---

## Checkpoints

- **CHECKPOINT A: después de T03** — El botón Editar en Index.vue funciona y navega a `/security/users/{id}/edit`. Verificar en browser con estudiante real.
- **CHECKPOINT B: después de T06** — La ruta `GET /academic/students/{id}` responde 200 con el JSON del resource. Verificar con `vendor/bin/sail artisan route:list`.
- **CHECKPOINT C: después de T08** — La página Show.vue renderiza sin errores TypeScript (`vendor/bin/sail npm run build`). Verificar en browser para estudiante universitario y estudiante de primaria.
- **CHECKPOINT D: después de T11** — Todos los tests de feature pasan. Build limpio.
