# Design — user-seeder-subrecord-fix

**Feature ID:** `user-seeder-subrecord-fix`

---

## Estrategia

`CreateUserAction::ensureSubRecord()` ya implementa exactamente la lógica necesaria. La solución más simple y correcta es replicar esa lógica en el seeder, usando `firstOrCreate` para garantizar idempotencia.

**Opciones consideradas:**

| Opción | Pros | Contras |
|--------|------|---------|
| Inyectar `CreateUserAction` en el seeder | Reutilización máxima | Acoplamiento del seeder a una Action; la Action tiene dependencias de sistema (Password facade) |
| Extraer `ensureSubRecord` a un trait/helper compartido | DRY perfecto | Overhead de refactor mayor para un método de 8 líneas |
| Replicar la lógica inline en el seeder | Mínimo, explícito, sin dependencias extra | Duplicación leve del `match` |

**Opción elegida:** Replicar inline. El seeder añade un método privado `ensureSubRecord(User $user, string $roleName): void` con el mismo `match` que el Action. Es el cambio más pequeño y no introduce dependencias entre capas (seeders y Actions).

---

## Cambio en UserSeeder.php

**Archivo:** `database/seeders/UserSeeder.php`

Agregar imports:
```php
use App\Enums\EducationalLevel;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
```

Modificar el método `run()` para llamar `ensureSubRecord()` después de `syncRoles()`:

```php
public function run(): void
{
    $data = Yaml::parseFile(database_path('data/users.yaml'));

    foreach ($data['users'] as $userData) {
        $existing = User::where('email', $userData['email'])->first();

        if ($existing) {
            $existing->syncRoles([$userData['role']]);
            $this->ensureSubRecord($existing, $userData['role']);  // ← NUEVO
            continue;
        }

        $nameParts = explode(' ', (string) $userData['name'], 2);

        $user = User::factory()->create([
            'first_name' => $nameParts[0] ?? $userData['name'],
            'last_name'  => $nameParts[1] ?? '',
            'email'      => $userData['email'],
            'password'   => Hash::make($userData['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$userData['role']]);
        $this->ensureSubRecord($user, $userData['role']);  // ← NUEVO
    }
}

private function ensureSubRecord(User $user, string $roleName): void
{
    match ($roleName) {
        'Profesor' => Professor::firstOrCreate(['user_id' => $user->id]),
        'Estudiante' => Student::firstOrCreate(
            ['user_id' => $user->id],
            ['educational_level' => EducationalLevel::University]
        ),
        'Representante' => Guardian::firstOrCreate(['user_id' => $user->id]),
        default => null,
    };
}
```

**Idempotencia garantizada:** `firstOrCreate` nunca duplica filas. Si el sub-registro ya existe, lo devuelve sin crear uno nuevo.

---

## Test

**Archivo nuevo:** `tests/Feature/UserSeederTest.php` (o integrar en `DatabaseSeeder` integration test si existe)

Test principal:
```php
it('creates professor sub-record for Profesor role', function () {
    $this->seed(UserSeeder::class);
    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();
    expect($user)->not->toBeNull();
    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
});

it('creates student sub-record for Estudiante role', function () {
    $this->seed(UserSeeder::class);
    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))->first();
    expect($user)->not->toBeNull();
    expect(Student::where('user_id', $user->id)->exists())->toBeTrue();
});

it('seeder is idempotent — running twice does not duplicate sub-records', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);
    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();
    expect(Professor::where('user_id', $user->id)->count())->toBe(1);
});
```

---

## Impacto

| Archivo | Cambio |
|---------|--------|
| `database/seeders/UserSeeder.php` | +3 imports + 2 llamadas a `ensureSubRecord()` + método privado (~12 líneas) |
| `tests/Feature/UserSeederTest.php` | nuevo archivo con 3 tests |

Sin migraciones. Sin cambios en frontend ni controllers.
