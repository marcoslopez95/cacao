# Requirements — role-sub-record-integrity

**Feature ID:** `role-sub-record-integrity`
**QA Hallazgos:** HLZ-02, HLZ-07
**Prioridad:** MEDIA — no bloquea carga de página, pero causa early-return silencioso al guardar

---

## Problema

Existen usuarios en la DB con un rol Spatie asignado pero sin el sub-registro correspondiente en la tabla del rol:

| Rol Spatie | Tabla de sub-registro | user_id afectados (dev) |
|---|---|---|
| `Representante` (→ `guardian`) | `guardians` | user_id=198 |
| `Profesor` (→ `professor`) | `professors` | user_id=2 |
| `Estudiante` (→ `student`) | `students` | user_id=3 |

En los tres casos, el comportamiento es:
1. `User::edit()` no encuentra el sub-registro → `props.professor`, `props.student` o `props.guardian` llega como `undefined`
2. El tab del rol es visible en el formulario (el rol está asignado)
3. Al intentar guardar la sección del rol, el handler hace `if (!props.professor) return` — silencioso, sin feedback
4. Los campos del perfil nunca se guardan

### Por qué ocurre esto

`CreateUserAction` y `UpdateUserAction` son muy simples: crean el usuario, sincronizan los roles, y no crean el sub-registro. El sub-registro debe crearse en otro flujo (p.ej. `StoreProfessorRequest` por separado), pero ese flujo no siempre se ejecuta.

En producción, si un admin cambia el rol de un usuario sin roles a `Profesor` mediante `UpdateUserAction`, el usuario queda con rol `Profesor` pero sin fila en `professors`.

---

## Alcance

### 1. Corrección preventiva: `firstOrCreate` al sincronizar roles

Al asignar un rol en `CreateUserAction` y `UpdateUserAction`, crear automáticamente el sub-registro si no existe:

- Rol `Profesor` → `Professor::firstOrCreate(['user_id' => $user->id])`
- Rol `Estudiante` → `Student::firstOrCreate(['user_id' => $user->id, 'educational_level' => EducationalLevel::University])`
- Rol `Representante` → `Guardian::firstOrCreate(['user_id' => $user->id])`

**Nota sobre `Student`:** `Student` tiene campos requeridos (`educational_level`). Al hacer `firstOrCreate` hay que proporcionar un valor por defecto razonable para `educational_level` (p.ej. `EducationalLevel::University`).

### 2. Comando artisan para reparar registros existentes: `users:fix-orphan-records`

Un comando `php artisan users:fix-orphan-records` que:
1. Itera usuarios con rol `Profesor` sin fila en `professors` → crea la fila
2. Itera usuarios con rol `Estudiante` sin fila en `students` → crea la fila
3. Itera usuarios con rol `Representante` sin fila en `guardians` → crea la fila
4. Reporta cuántos registros huérfanos se repararon

### 3. Tests

- Test: `CreateUserAction con rol Profesor crea fila en professors`
- Test: `CreateUserAction con rol Estudiante crea fila en students`
- Test: `CreateUserAction con rol Representante crea fila en guardians`
- Test: `UpdateUserAction al cambiar rol a Profesor crea fila en professors si no existe`
- Test: `users:fix-orphan-records repara usuarios con rol sin sub-registro`

---

## Precondiciones

- Los modelos `Professor`, `Student`, `Guardian` tienen factory
- El enum `EducationalLevel` existe en `app/Enums/EducationalLevel.php`
- `students.educational_level` es una columna requerida (no nullable)

---

## Criterios de aceptación

1. Crear usuario con rol `Profesor` → fila en `professors` creada automáticamente
2. Crear usuario con rol `Estudiante` → fila en `students` creada automáticamente con `educational_level` por defecto
3. Crear usuario con rol `Representante` → fila en `guardians` creada automáticamente
4. Actualizar usuario cambiando rol a `Profesor` → fila en `professors` creada si no existe
5. `php artisan users:fix-orphan-records` repara user_id=2, 3, 198 en el entorno de dev
6. Después de `fix-orphan-records`, `props.professor`, `props.student`, `props.guardian` dejan de llegar como `undefined` para esos usuarios

---

## Archivos afectados

- `app/Actions/Security/CreateUserAction.php`
- `app/Actions/Security/UpdateUserAction.php`
- `app/Console/Commands/FixOrphanUserRecordsCommand.php` (nuevo)
- `tests/Feature/Security/UserControllerTest.php` o nuevo test file
