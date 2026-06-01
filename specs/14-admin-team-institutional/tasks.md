# Tasks — Admin Team Institucional (feature 14)

- [x] Task 1 — AdminTeamSeeder: crear `database/seeders/AdminTeamSeeder.php` con `firstOrCreate(slug='admin')` + asignación idempotente de todos los admins existentes como miembros del team
- [x] Task 2 — CreateUserAction: tras crear el usuario, si `getRoleName() === 'admin'`, hacer attach al team `admin` (guard defensivo si el team no existe aún)
- [x] Task 3 — ResolvesLoginRedirect: para rol Admin, resolver team por slug `admin` vía `$user->teams()->where('slug','admin')->first()`; `switchTeam()` y redirect a `/admin/dashboard`; fallback a `personalTeam()` si no hay membresía
- [x] Task 4 — TeamPolicy: método `delete()` retorna `false` si `$team->slug === 'admin'`; de lo contrario delega a `$user->ownsTeam($team)`
- [x] Task 5 — DatabaseSeeder: registrar `AdminTeamSeeder::class` después de los seeders de usuarios; ejecutar en entorno dev para verificar idempotencia
- [x] Task 6 — QA Gate: qa_manager verifica todos los UCs de specs/14-admin-team-institutional/qa.md en verde
