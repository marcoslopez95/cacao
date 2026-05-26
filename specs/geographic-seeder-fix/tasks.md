# Tasks — geographic-seeder-fix

**Feature:** `geographic-seeder-fix`
**Scope:** Backend PHP — seeder + tests
**Depends on:** ninguno

---

## Tasks

- [x] T01 — Investigar: leer `database/seeders/DatabaseSeeder.php` completo para verificar si `GeographicSeeder` está registrado en el flujo de `db:seed`
- [x] T02 — Investigar: ejecutar `vendor/bin/sail artisan db:seed --class="Database\\Seeders\\Catalogs\\GeographicSeeder"` y verificar que puebla `countries` y `states` sin errores
- [x] T03 — Según hallazgo de T01: si `GeographicSeeder` no está en `DatabaseSeeder`, registrarlo en el orden correcto (antes de seeders que dependan de geografía)
- [x] T04 — Ejecutar `vendor/bin/sail artisan db:seed` completo en entorno de desarrollo y verificar que `countries` y `states` tienen datos (`Country::count()` > 0, `State::count()` > 0)
- [x] T05 — Verificar visualmente en `/security/users/{id}/edit` que el select de País en S03 muestra opciones
- [x] T06 — Agregar test en `UserEditUseCaseTest.php`: `edit page catalogData includes countries when geographic data is seeded` — verifica que `catalogData.countries` no está vacío
- [x] T07 — Ejecutar `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest` — todos los tests pasan incluyendo el nuevo
- [x] T08 — `vendor/bin/sail bin pint --dirty --format agent` si se modificaron archivos PHP

---

## Checkpoints

- **CHECKPOINT A: después de T02** — El seeder funciona sin errores cuando se ejecuta manualmente. Si falla, corregir antes de continuar. ✅
- **CHECKPOINT B: después de T04** — La DB de desarrollo tiene países y estados. El select S03 muestra opciones. ✅ (187 countries, 24 states)
- **CHECKPOINT C: después de T07** — El test nuevo pasa. `beforeEach` con `$this->seed(GeographicSeeder::class)` garantiza datos geográficos en todos los tests de edición. ✅ (31/31 passed)
