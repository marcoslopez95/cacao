# QA Spec — Admin Team Institucional
**Feature:** 14-admin-team-institutional
**Fecha:** 2026-05-31

---

## UCs a cubrir con Dusk

### UC-QA-01 — Login Admin redirige a `/admin/dashboard`

**Precondición:**
- El seeder ha corrido: existe el team `admin` (slug=`admin`) en DB.
- Existe al menos un usuario con rol `admin` y membresía en el team `admin`.
- El usuario está deslogueado.

**Pasos:**
1. Navegar a `/login`.
2. Completar el formulario con las credenciales del admin.
3. Hacer clic en "Iniciar sesión".

**Resultado esperado:**
- La URL final del browser es `/admin/dashboard`.
- La página carga sin error (código 200, componente `Admin/Dashboard` visible).
- `users.current_team_id` en DB apunta al team con slug `admin`.

**Test Dusk:** pendiente

---

### UC-QA-02 — Admin creado desde UI aparece en team `admin` y accede a `/admin/dashboard`

**Precondición:**
- El team `admin` existe en DB.
- Se está logueado como admin en la sesión activa.
- El formulario de creación de usuarios está accesible en `/security/users/create`.

**Pasos:**
1. Navegar a `/security/users/create`.
2. Completar el formulario con rol = `Admin` y datos válidos.
3. Guardar el formulario.
4. Verificar en DB que el nuevo usuario tiene membresía en el team `admin`.
5. Hacer logout.
6. Login con las credenciales del nuevo admin.

**Resultado esperado:**
- Tras guardar, el nuevo usuario existe en `team_user` con `team_id` del team `admin`.
- Tras el login del nuevo admin, la URL final es `/admin/dashboard`.

**Test Dusk:** pendiente

---

### UC-QA-03 — Otros roles no redirigen a `/admin/dashboard`

**Precondición:**
- Existen usuarios con roles `profesor`, `estudiante` y `representante` en DB.
- Todos están deslogueados.

**Pasos (repetir para cada rol no-admin):**
1. Navegar a `/login`.
2. Completar con credenciales del usuario del rol en cuestión.
3. Hacer clic en "Iniciar sesión".

**Resultado esperado:**
- Rol `profesor`: URL final es `/professor/dashboard` (o equivalente del portal profesor).
- Rol `estudiante`: URL final es `/student/dashboard` (o equivalente del portal estudiante).
- Rol `representante`: URL final es `/guardian/dashboard` (o equivalente del portal representante).
- Ninguno llega a `/admin/dashboard`.

**Test Dusk:** pendiente

---

### UC-QA-04 — Seeder idempotente: team `admin` creado exactamente una vez

**Precondición:**
- La DB puede tener o no el team `admin` (ambos escenarios se prueban).

**Pasos:**
1. Ejecutar `php artisan db:seed --class=AdminTeamSeeder`.
2. Registrar el `id` del team `admin` en DB.
3. Ejecutar `php artisan db:seed --class=AdminTeamSeeder` una segunda vez.
4. Consultar `SELECT COUNT(*) FROM teams WHERE slug = 'admin'`.

**Resultado esperado:**
- El COUNT es exactamente `1` (no se creó un segundo team).
- El `id` del team es el mismo antes y después de la segunda ejecución.
- El seeder no lanza ninguna excepción en ninguna de las dos ejecuciones.
- Los admins existentes tienen membresía en el team `admin` (sin duplicados en `team_user`).

**Test Dusk:** pendiente (test feature/unit — no requiere browser; se puede implementar como Pest Feature test que llama al seeder dos veces y verifica el COUNT vía `assertDatabaseCount`)
