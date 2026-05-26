# Design — geographic-seeder-fix

**Feature ID:** `geographic-seeder-fix`

---

## Investigación previa a implementación

Antes de hacer cambios, ejecutar:

```bash
# Ver si GeographicSeeder está registrado en DatabaseSeeder
grep -n "GeographicSeeder" /var/www/cacao/database/seeders/DatabaseSeeder.php

# Ver qué seeders se llaman
cat /var/www/cacao/database/seeders/DatabaseSeeder.php

# Ver el contenido de venezuela/states.json
wc -c /var/www/cacao/database/seeders/data/venezuela/states.json

# Ejecutar el seeder directamente para ver si funciona
vendor/bin/sail artisan db:seed --class="Database\\Seeders\\Catalogs\\GeographicSeeder"

# Verificar que puebla datos
vendor/bin/sail artisan tinker --execute 'echo Country::count() . " countries, " . State::count() . " states";'
```

---

## Hipótesis

El seeder funciona correctamente (los archivos JSON existen). La causa más probable es que `GeographicSeeder` no está siendo llamado desde `DatabaseSeeder` en el flujo de `db:seed` estándar.

---

## Acciones según hallazgo de investigación

### Caso A: GeographicSeeder no está en DatabaseSeeder

Agregar en `DatabaseSeeder::run()` la llamada a `GeographicSeeder` en el orden correcto (antes de seeders que dependan de países/estados):

```php
$this->call([
    GeographicSeeder::class,
    // ... otros
]);
```

### Caso B: GeographicSeeder falla silenciosamente

Revisar la lógica de `seedCountries()` y `seedStates()`. Agregar manejo de errores más explícito o logging si falla `json_decode`.

### Caso C: El seeder funciona pero los datos se borraron

Simplemente ejecutar el seeder en el entorno actual. No requiere cambio de código.

---

## Test nuevo: `catalogData.countries` no vacío

Agregar en `UserEditUseCaseTest.php`:

```php
test('edit page catalogData includes countries when geographic data is seeded', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.countries')
            ->where('catalogData.countries', fn ($val) => count($val) > 0)
        );
});
```

**Nota:** El `beforeEach` ya llama `$this->seed(GeographicSeeder::class)`, por lo que este test debería pasar una vez el seeder funcione correctamente.

---

## Impacto

- Sin cambios de schema (tablas `countries` y `states` ya existen con la estructura correcta)
- Posible cambio en `DatabaseSeeder.php` para registrar el seeder
- Un test nuevo en `UserEditUseCaseTest.php`
- Datos de seed para el entorno de desarrollo
