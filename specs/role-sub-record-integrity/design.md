# Design — role-sub-record-integrity

**Feature ID:** `role-sub-record-integrity`

---

## Decisión de diseño

### Dónde crear los sub-registros

La creación del sub-registro debe ocurrir **en las Actions**, después de `syncRoles()`. Esto sigue el principio de que los Actions contienen la lógica de negocio.

No se usa un evento Eloquent (`User::updated`) porque:
- Los eventos son difíciles de debuggear y testear
- La lógica quedaría escondida lejos del flujo obvio
- La convención del proyecto es Actions + Wrappers, no observers/events

### Lógica en `CreateUserAction`

```php
public function handle(UserWrapper $wrapper): User
{
    $user = User::create($wrapper->getStoreData());
    $user->syncRoles([$wrapper->getRoleName()]);
    $this->ensureSubRecord($user, $wrapper->getRoleName());

    if ($wrapper->sendsResetLink()) {
        Password::sendResetLink(['email' => $wrapper->getEmail()]);
    }

    return $user;
}

private function ensureSubRecord(User $user, string $roleName): void
{
    match ($roleName) {
        'Profesor'      => Professor::firstOrCreate(['user_id' => $user->id]),
        'Estudiante'    => Student::firstOrCreate(
                              ['user_id' => $user->id],
                              ['educational_level' => EducationalLevel::University]
                          ),
        'Representante' => Guardian::firstOrCreate(['user_id' => $user->id]),
        default         => null,
    };
}
```

### Lógica en `UpdateUserAction`

`syncRoles()` puede asignar uno o más roles. Iterar sobre los roles nuevos:

```php
public function handle(User $user, UserWrapper $wrapper): User
{
    $user->update($wrapper->getUpdateData());
    $user->syncRoles($wrapper->getRoles());

    foreach ($wrapper->getRoles() as $roleName) {
        $this->ensureSubRecord($user, $roleName);
    }

    return $user;
}
```

**Consideración:** si un usuario pierde el rol `Profesor` (se le asigna otro rol), no se elimina la fila en `professors`. Esto es correcto — la eliminación es siempre manual (FK RESTRICT). Solo se crean, nunca se destruyen automáticamente.

### Mapeo de roles

Los nombres de rol Spatie en el sistema son:
- `'Profesor'` → `professors`
- `'Estudiante'` → `students`
- `'Representante'` → `guardians`
- `'Admin'`, `'Administrador'`, `'Coordinador'` → no tienen sub-registro

### `Student` — valor por defecto de `educational_level`

`students.educational_level` es NOT NULL y usa el enum `EducationalLevel`. El valor por defecto para un sub-registro auto-creado es `EducationalLevel::University`. Si la institución usa nivel no universitario, el admin puede cambiarlo después desde S08.

### Comando artisan `users:fix-orphan-records`

```php
// app/Console/Commands/FixOrphanUserRecordsCommand.php
class FixOrphanUserRecordsCommand extends Command
{
    protected $signature = 'users:fix-orphan-records {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Create missing sub-records for users with a role but no matching row in professors/students/guardians';

    public function handle(): int
    {
        $fixed = 0;

        // Profesores sin fila en professors
        User::role('Profesor')
            ->whereDoesntHave('professor')
            ->each(function (User $user) use (&$fixed) {
                if (!$this->option('dry-run')) {
                    Professor::create(['user_id' => $user->id]);
                }
                $this->line("Fixed professor for user #{$user->id} ({$user->name})");
                $fixed++;
            });

        // Estudiantes sin fila en students
        User::role('Estudiante')
            ->whereDoesntHave('student')
            ->each(function (User $user) use (&$fixed) {
                if (!$this->option('dry-run')) {
                    Student::create([
                        'user_id'           => $user->id,
                        'educational_level' => EducationalLevel::University,
                    ]);
                }
                $this->line("Fixed student for user #{$user->id} ({$user->name})");
                $fixed++;
            });

        // Representantes sin fila en guardians
        User::role('Representante')
            ->whereDoesntHave('guardian')
            ->each(function (User $user) use (&$fixed) {
                if (!$this->option('dry-run')) {
                    Guardian::create(['user_id' => $user->id]);
                }
                $this->line("Fixed guardian for user #{$user->id} ({$user->name})");
                $fixed++;
            });

        $this->info("Fixed {$fixed} orphan record(s).");
        return Command::SUCCESS;
    }
}
```

---

## Impacto

- Sin migraciones (las tablas ya existen)
- Sin cambios de frontend
- `CreateUserAction` y `UpdateUserAction` ganan un método privado `ensureSubRecord()`
- Nuevo comando artisan para reparar datos existentes
- Tests en `UserControllerTest.php` o archivo nuevo `RoleSubRecordTest.php`
