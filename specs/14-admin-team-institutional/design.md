# Admin Team Institucional — Design

**Feature:** 14-admin-team-institutional
**Fecha:** 2026-05-31

---

## Decisión de diseño

**Opción elegida: Team institucional por slug fijo (`admin`)**

Se crea un único team institucional con `slug = 'admin'` e `is_personal = false`.
La resolución del team en `ResolvesLoginRedirect` y en `CreateUserAction` usa
el slug como identificador estable — nunca el ID de DB, que puede variar entre
entornos. El slug es único por diseño del modelo `Team` en Jetstream.

### Alternativas descartadas

**Team por ID de DB hardcodeado**
Descartado: el ID varía entre entornos (dev, staging, prod). El slug es la
clave semántica correcta y ya está indexado en la tabla `teams`.

**Constante de configuración (`config/teams.php`)**
Descartado para este scope: añade indirección sin beneficio real. Si en el futuro
el slug cambia (improbable), se trata como un cambio de infra, no de config.

**Sin team institucional — redirect por rol hardcodeado en middleware**
Descartado: rompe el contrato de Jetstream Teams. El team es el contexto de
autorización; hardcodear el redirect sin team dejaria `current_team_id` del
usuario sin actualizar, lo que rompería los Guards que dependen del team activo.

---

## Cambios técnicos por capa

### Base de datos

No se requieren migraciones. La tabla `teams` ya existe con columnas `slug`,
`is_personal`, `name`, `user_id`. El team se crea vía Seeder usando el
modelo `Team` de Jetstream.

### Seeders

**`AdminTeamSeeder`** (`database/seeders/AdminTeamSeeder.php`)

```php
Team::firstOrCreate(
    ['slug' => 'admin'],
    ['name' => 'Administración', 'is_personal' => false, 'user_id' => $firstAdmin->id]
);

// Luego asignar todos los admins existentes como miembros
$admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
foreach ($admins as $admin) {
    if (!$admin->belongsToTeam($adminTeam)) {
        $adminTeam->users()->attach($admin, ['role' => 'admin']);
    }
}
```

Idempotente: `firstOrCreate` no crea duplicados; el `if (!belongsToTeam)` no
duplica membresías.

### Actions

**`CreateUserAction`** (`app/Actions/Jetstream/CreateUserAction.php` o equivalente)

Tras el `User::create(...)` existente, añadir:

```php
if ($wrapper->getRoleName() === 'admin') {
    $adminTeam = Team::where('slug', 'admin')->first();
    if ($adminTeam) {
        $adminTeam->users()->attach($user, ['role' => 'admin']);
    }
}
```

El `if ($adminTeam)` es un guard defensivo: si el seeder no ha corrido aún
en un entorno nuevo, no explota — simplemente no asigna membresía.

### Policies

**`TeamPolicy`** (`app/Policies/TeamPolicy.php`)

Sobrescribir (o crear si no existe) el método `delete`:

```php
public function delete(User $user, Team $team): bool
{
    if ($team->slug === 'admin') {
        return false;
    }
    return $user->ownsTeam($team);
}
```

### Redirect post-login

**`ResolvesLoginRedirect`** (trait o clase que implementa `LoginResponse`)

Para el rol Admin, antes de resolver el personal team, intentar:

```php
if ($user->hasRole('admin')) {
    $adminTeam = $user->teams()->where('slug', 'admin')->first();
    $targetTeam = $adminTeam ?? $user->personalTeam();
    $user->switchTeam($targetTeam);
    return redirect('/admin/dashboard');
}
```

El fallback a `personalTeam()` cubre el edge case de admin sin membresía
en el team institucional (DB inconsistente por mantenimiento manual).

### DatabaseSeeder

Registrar `AdminTeamSeeder` en `DatabaseSeeder::run()` después de los
seeders de usuarios (ya que necesita que existan admins):

```php
$this->call([
    // ... seeders existentes ...
    AdminTeamSeeder::class,
]);
```

---

## Dependencias con otros módulos

| Módulo | Dependencia | Dirección |
|--------|-------------|-----------|
| `05-multi-role-login` | `ResolvesLoginRedirect` ya existe; se modifica | Extiende |
| `CreateUserAction` | Ya existe en el flujo de creación de usuarios | Modifica |
| Jetstream Teams | `Team`, `belongsToTeam()`, `switchTeam()` | Usa API existente |
| `TeamPolicy` | Puede existir o no; se crea/modifica | Crea o modifica |
| `DatabaseSeeder` | Registrar el nuevo seeder | Modifica |

---

## Notas de implementación

- El slug `admin` es la única fuente de verdad — no cachear el `team_id` en config.
- `switchTeam()` actualiza `current_team_id` en la sesión/usuario, garantizando que los Guards de Jetstream funcionen correctamente.
- Los tests Dusk usan el usuario admin del `DemoSeeder` (o el seeder de test equivalente); el `AdminTeamSeeder` debe correr antes de los tests que verifican redirect.
