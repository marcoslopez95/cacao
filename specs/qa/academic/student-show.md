# UC — Student Show & Edit Wiring

**Feature de origen:** `student-academic-show`
**Última verificación:** 2026-05-25
**Estado:** VERIFICADO

---

## UC-01 — Ver perfil de estudiante (Nivel 2)

**Precondición:** Admin autenticado en `/academic/students`

**Pasos:** Click botón ojo (Ver perfil) en cualquier fila de la tabla → navega a `/academic/students/{id}`

**Resultado esperado:** Página `admin/Students/Show.vue` con las siguientes secciones:
- **Identidad:** nombre completo, email, cédula, código de estudiante
- **Carrera / pensum activo:** nombre de la carrera, nombre del pensum
- **Año académico, modalidad, turno, estado académico**
- **Inscripción del período activo:** estado (draft/confirmed/approved/rejected), UC inscritas, UC disponibles (colapsada si no hay)
- **Representantes** (solo para nivel primaria y bachillerato): nombre, email, flag principal
- **Historial de inscripciones por período:** tabla con período, estado, UC inscritas
- Botones Volver (→ índice) y Editar (→ `/security/users/{user_id}/edit`) en el header

**Verificado por:** Feature tests (8 acceptance tests en `tests/Feature/Academic/Acceptance/StudentShowAcceptanceTest.php`)
- GET /academic/students/{id} → 200, componente `admin/Students/Show`
- identity fields expuestos correctamente (id, user_id, name, email, educational_level, career_name, pensum_name)
- guardians array presente para nivel primario con datos correctos
- active_enrollment presente cuando hay período activo con inscripción confirmada
- active_enrollment es null cuando no hay período activo
- 404 para ID inexistente
- redirect a /login para usuario no autenticado
- user_id expuesto en StudentListResource para wiring del botón Editar

**Test Dusk:** `tests/Browser/Academic/StudentShowTest.php` — pendiente escritura

---

## UC-02 — Editar estudiante desde índice académico

**Precondición:** Admin autenticado en `/academic/students`

**Pasos:** Click botón lápiz (Editar) en cualquier fila de la tabla → navega a `/security/users/{student.user_id}/edit`

**Resultado esperado:** Carga el formulario de edición de usuario de 17 secciones (página ya existente `security/Users/Edit.vue`) con todos los datos del estudiante pre-cargados.

**Verificado por:** Acceptance test `StudentListResource exposes user_id for each student row` — confirma que `user_id` está disponible en cada fila. El wiring de navegación está en `Index.vue` con `<Link :href="editUser({ user: s.user_id }).url">`.

**Test Dusk:** `tests/Browser/Academic/StudentShowTest.php` — pendiente escritura

---

## Notas de diseño

- UC-02 no requiere nueva ruta ni controlador — es wiring de navegación puro en `Index.vue`
- UC-01 requiere nueva ruta `GET /academic/students/{student}`, método `show()` en `StudentController`, nuevo `StudentShowResource`, y nueva página `admin/Students/Show.vue`
- El botón Editar navega a Security (formulario personal completo); el botón Ver navega a Show (perfil académico de lectura)
- La página Show es read-only — no tiene formulario ni guards de CASL porque es solo admin
- `StudentShowResource` usa `->resolve()` al pasarse a Inertia para evitar wrapping en `{data: ...}`
