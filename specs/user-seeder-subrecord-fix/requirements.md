# Requirements — user-seeder-subrecord-fix

**Feature ID:** `user-seeder-subrecord-fix`
**QA Hallazgos:** HLZ-22
**Prioridad:** ALTA — cada `migrate:fresh --seed` reproduce el estado huérfano de HLZ-07; rompe entorno de desarrollo en la primera ejecución

---

## Problema

`database/seeders/UserSeeder.php` crea usuarios y les asigna roles via `syncRoles()`, pero **nunca crea los sub-registros correspondientes** (`professors`, `students`, `guardians`).

Consecuencia directa: cada vez que se ejecuta `php artisan migrate:fresh --seed` (entornos frescos, CI, onboarding de nuevos devs), los usuarios demo quedan en estado huérfano:
- "Profesor Demo" (rol `Profesor`, seeder crea `user_id=2`) → sin fila en `professors`
- "Estudiante Demo" (rol `Estudiante`, seeder crea `user_id=3`) → sin fila en `students`
- Cualquier guardian demo → sin fila en `guardians`

Este estado hace que las secciones del formulario de edición relacionadas con el rol (S08-S17 para estudiante, S17 para profesor) hagan early return silencioso y nunca puedan completarse.

**La lógica correcta ya existe:** `CreateUserAction::ensureSubRecord()` tiene exactamente el código necesario. El seeder simplemente no lo llama.

---

## Precondiciones

- `CreateUserAction::ensureSubRecord()` existe y funciona correctamente (fue validado en `role-sub-record-integrity`)
- `UserSeeder.php` usa `Symfony\Component\Yaml\Yaml` para parsear `database/data/users.yaml`
- Los modelos `Professor`, `Student`, `Guardian` existen con sus factories

---

## Criterios de aceptación

1. **RF-01** — Después de un `php artisan migrate:fresh --seed`, el "Profesor Demo" tiene exactamente una fila en `professors` con `user_id` igual al id del usuario creado.
2. **RF-02** — Después de un `php artisan migrate:fresh --seed`, el "Estudiante Demo" tiene exactamente una fila en `students` con `user_id` igual al id del usuario creado.
3. **RF-03** — El seeder es idempotente: ejecutarlo dos veces no crea duplicados en `professors`, `students` ni `guardians` (usar `firstOrCreate`).
4. **RF-04** — El seeder funciona tanto para usuarios nuevos (create path) como para usuarios ya existentes (update path con `syncRoles`).
5. **RF-05** — Test: "UserSeeder crea sub-registro correcto según el rol del usuario".

---

## Archivos afectados

**Backend (PHP):**
- `database/seeders/UserSeeder.php` — agregar llamada a `ensureSubRecord()` (o lógica equivalente inline) después de `syncRoles()`

**Tests:**
- `tests/Feature/` — nuevo test `UserSeederTest.php` o agregar test a `DatabaseSeeder` integration
