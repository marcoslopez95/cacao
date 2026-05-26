# Tasks — role-sub-record-integrity

**Feature:** `role-sub-record-integrity`
**Scope:** Backend PHP — Actions + comando artisan + tests
**Depends on:** ninguno

---

## Tasks

- [x] T01 — `CreateUserAction`: agregar método privado `ensureSubRecord(User $user, string $roleName): void` que llama `Professor::firstOrCreate`, `Student::firstOrCreate` o `Guardian::firstOrCreate` según el rol asignado
- [x] T02 — `CreateUserAction::handle()`: llamar `$this->ensureSubRecord($user, $wrapper->getRoleName())` después de `$user->syncRoles()`
- [x] T03 — `UpdateUserAction`: agregar método privado `ensureSubRecord(User $user, string $roleName): void` (misma lógica que T01)
- [x] T04 — `UpdateUserAction::handle()`: iterar `$wrapper->getRoles()` y llamar `$this->ensureSubRecord($user, $roleName)` para cada rol después de `$user->syncRoles()`
- [x] T05 — Crear `app/Console/Commands/FixOrphanUserRecordsCommand.php`: comando `users:fix-orphan-records` con opción `--dry-run`, que itera usuarios con rol pero sin sub-registro y crea las filas faltantes
- [x] T06 — Registrar el comando en `app/Console/Kernel.php` (o equivalente en Laravel 13 — verificar si usa `bootstrap/app.php` para commands)
- [x] T07 — Test: `crear usuario con rol Profesor crea fila en professors` — usar `User::factory()`, asignar rol `Profesor`, verificar `Professor::where('user_id', $user->id)->exists()`
- [x] T08 — Test: `crear usuario con rol Estudiante crea fila en students` — verificar que `Student::where('user_id', ...)` existe con `educational_level` válido
- [x] T09 — Test: `crear usuario con rol Representante crea fila en guardians`
- [x] T10 — Test: `actualizar usuario cambiando rol a Profesor crea fila en professors si no existe` — usar `UpdateUserAction` directamente o via `PATCH /security/users/{user}`
- [x] T11 — Test: `users:fix-orphan-records crea sub-registros faltantes` — crear usuario con rol sin sub-registro, ejecutar el comando con `artisan()`, verificar que se creó la fila
- [x] T12 — Test: `users:fix-orphan-records con --dry-run no modifica datos` — verificar que en dry-run no se crean filas
- [x] T13 — Ejecutar `php artisan users:fix-orphan-records` en el entorno de desarrollo — verificar que repara user_id=2, 3, 198
- [x] T14 — Ejecutar `vendor/bin/sail artisan test --compact --filter=RoleSubRecord` (o el filtro del archivo de test) — todos los tests pasan
- [x] T15 — `vendor/bin/sail bin pint --dirty --format agent` sobre los archivos PHP modificados

---

## Checkpoints

- **CHECKPOINT A: después de T04** — `CreateUserAction` y `UpdateUserAction` llaman a `ensureSubRecord()`. Crear un usuario con rol `Profesor` desde la UI genera una fila en `professors`.
- **CHECKPOINT B: después de T06** — El comando artisan existe y es invocable con `php artisan users:fix-orphan-records --dry-run`.
- **CHECKPOINT C: después de T12** — Los tests de Actions y del comando pasan.
- **CHECKPOINT D: después de T13** — Los 3 usuarios huérfanos en el entorno de dev tienen sus sub-registros. Al visitar `/security/users/198/edit` (guardian), `props.guardian` ya no es `undefined`.
