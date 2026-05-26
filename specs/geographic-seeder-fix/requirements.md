# Requirements — geographic-seeder-fix

**Feature ID:** `geographic-seeder-fix`
**QA Hallazgos:** HLZ-01, HLZ-03
**Prioridad:** ALTA — bloquea selects de País/Estado en S03 y puede romper CI

---

## Problema

La tabla `countries` tiene 0 filas y `states` tiene 0 filas en el entorno de desarrollo actual. Los selects de País y Estado en S03 (Dirección) del formulario de edición de usuario aparecen completamente vacíos.

### HLZ-01 — Catálogos geográficos vacíos en DB de desarrollo

- `countries`: 0 filas
- `states`: 0 filas
- `GeographicSeeder` existe y es funcional
- El seeder no ha sido ejecutado en el entorno de desarrollo actual

Consecuencia en UI: `catalogData.countries` llega como `[]` y `catalogData.states` llega como `[]`. Los selects en S03 no tienen opciones. El campo no se puede completar correctamente.

### HLZ-03 — `states.json` ausente puede romper el seeder en entornos frescos

**Hallazgo del audit QA:** el archivo `database/seeders/data/states.json` no existía en el repo al momento de la auditoría. Sin embargo, al revisar el código actual, se encontró que existe el archivo en `database/seeders/data/venezuela/states.json` y el `GeographicSeeder` lo carga correctamente desde esa ruta.

**Estado real:** `venezuela/states.json` (1,201 bytes) existe con 24 estados de Venezuela. La causa del problema es que el seeder no fue ejecutado en el entorno de desarrollo, no que el archivo falte.

Sin embargo, los tests en `UserEditUseCaseTest.php` que verifican S03 (p.ej. `S3 address: admin can create a primary address`) hacen `$this->seed(GeographicSeeder::class)` en `beforeEach` — si el seeder funciona correctamente, los tests deberían pasar en entornos frescos con `migrate:fresh --seed`.

---

## Investigación requerida

Antes de implementar, verificar:

1. ¿Por qué `countries` tiene 0 filas en el entorno actual? — ¿El seeder no fue llamado en `DatabaseSeeder`? ¿Falló silenciosamente?
2. ¿`GeographicSeeder` está registrado en `DatabaseSeeder::run()`?
3. ¿Existe algún test que verifique que `catalogData.countries` no está vacío?

---

## Criterios de aceptación

1. Ejecutar `vendor/bin/sail artisan db:seed --class=Database\\Seeders\\Catalogs\\GeographicSeeder` puebla `countries` (>1 país) y `states` (≥24 estados venezolanos)
2. El select de País en S03 muestra opciones al cargar `/security/users/{id}/edit`
3. El select de Estado en S03 filtra correctamente por país seleccionado
4. `vendor/bin/sail artisan migrate:fresh --seed` en un entorno limpio ejecuta el seeder sin errores
5. Test nuevo en `UserEditUseCaseTest.php`: `edit page includes countries in catalogData` — verifica que `catalogData.countries` tiene al menos un elemento cuando existe al menos un país activo

---

## Archivos probablemente afectados

- `database/seeders/DatabaseSeeder.php` — verificar si `GeographicSeeder` está registrado
- `database/seeders/Catalogs/GeographicSeeder.php` — revisar si hay problemas en la lógica
- `tests/Feature/Security/UserEditUseCaseTest.php` — agregar test de catalogData no vacío
