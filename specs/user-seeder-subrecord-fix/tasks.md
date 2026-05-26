# Tasks — user-seeder-subrecord-fix

**Feature:** `user-seeder-subrecord-fix`
**Scope:** 1 seeder PHP + 1 test
**Depends on:** ninguno (independiente)
**HLZs resueltos:** HLZ-22

---

## Task 01 — UserSeeder: agregar ensureSubRecord() y llamarlo

- [x] Abrir `database/seeders/UserSeeder.php`
- [x] Agregar imports al inicio del archivo:
  ```php
  use App\Enums\EducationalLevel;
  use App\Models\Guardian;
  use App\Models\Professor;
  use App\Models\Student;
  ```
- [x] En el path de usuario EXISTENTE (bloque `if ($existing)`), agregar después de `$existing->syncRoles(...)`:
  ```php
  $this->ensureSubRecord($existing, $userData['role']);
  ```
- [x] En el path de usuario NUEVO (después de `$user->syncRoles(...)`), agregar:
  ```php
  $this->ensureSubRecord($user, $userData['role']);
  ```
- [x] Agregar el método privado al final de la clase:
  ```php
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
- [x] Ejecutar `vendor/bin/sail bin pint --dirty --format agent` sobre `UserSeeder.php`

**Acceptance:** `php artisan db:seed --class=UserSeeder` en DB fresca → `Professor::where('user_id', $profesorUserId)->exists()` es `true`.

---

## Task 02 — Test: UserSeederTest

- [x] Crear `tests/Feature/UserSeederTest.php` con `php artisan make:test --pest UserSeederTest`
- [x] Agregar `use RefreshDatabase;` (o `uses(RefreshDatabase::class)` en estilo Pest)
- [x] Agregar test 1: "crea sub-registro professors para rol Profesor"
  - `$this->seed(\Database\Seeders\UserSeeder::class)`
  - `$user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first()`
  - `expect($user)->not->toBeNull()`
  - `expect(Professor::where('user_id', $user->id)->exists())->toBeTrue()`
- [x] Agregar test 2: "crea sub-registro students para rol Estudiante"
  - Ídem pero para `Estudiante` y `Student`
- [x] Agregar test 3: "es idempotente — doble ejecución no duplica sub-registros"
  - `$this->seed(...)` dos veces
  - `expect(Professor::where('user_id', $user->id)->count())->toBe(1)`

**Acceptance:** `vendor/bin/sail artisan test --compact --filter=UserSeederTest` — los 3 tests pasan en verde.

---

## Task 03 — Verificación con migrate:fresh --seed

- [x] Ejecutar `vendor/bin/sail artisan migrate:fresh --seed` en entorno de desarrollo
- [x] Confirmar con tinker: `Professor::where('user_id', 2)->exists()` → `true`
- [x] Confirmar: `Student::where('user_id', 3)->exists()` → `true`
- [x] Confirmar que `users:fix-orphan-records` ya no reporta huérfanos para los usuarios demo del seeder

**Acceptance:** DB fresca no tiene usuarios demo huérfanos. `users:fix-orphan-records` reporta "0 orphans found".
