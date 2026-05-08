# Spec 03 — Profesores

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** módulo de seguridad (users + roles)
**Bloquea:** Secciones (Specs 04-05), Horarios (Specs 06-07)

---

## Contexto

Un `Professor` es el perfil académico mínimo de un usuario con rol `Profesor`. Almacena solo los datos necesarios para operar el módulo de horarios: el vínculo al usuario y el límite de horas semanales.

El perfil completo (cédula, especialidad, grado académico, dirección, teléfono) se desarrollará en el módulo de levantamiento socioeconómico/profesional.

---

## Modelo de datos

### `professors`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → users | RESTRICT on delete, único |
| `weekly_hour_limit` | smallint unsigned | default 20, mínimo 1 |
| `active` | boolean | default `true` |
| `created_at` / `updated_at` | timestamps | |

---

## Reglas de negocio

- Un usuario solo puede tener un registro de profesor (`user_id` único).
- El usuario vinculado debe tener el rol `Profesor` en el sistema (Spatie).
- Desactivar un profesor no elimina sus secciones ni horarios; solo impide nuevas asignaciones (validado al crear secciones/horarios en Specs posteriores).
- No se puede eliminar un profesor con secciones o horarios asignados (FK RESTRICT aplicado en Specs 04-07).

---

## Backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`.

### Rutas

```
GET    /scheduling/professors                scheduling.professors.index
POST   /scheduling/professors                scheduling.professors.store
PATCH  /scheduling/professors/{professor}    scheduling.professors.update
DELETE /scheduling/professors/{professor}    scheduling.professors.destroy
```

### Archivos PHP

```
app/Models/Professor.php
database/migrations/XXXX_create_professors_table.php
database/factories/ProfessorFactory.php           — crea User con rol Profesor vinculado
app/Policies/ProfessorPolicy.php
app/Http/Requests/Scheduling/StoreProfessorRequest.php
app/Http/Requests/Scheduling/UpdateProfessorRequest.php
app/Http/Wrappers/Scheduling/ProfessorWrapper.php
app/Http/Resources/Scheduling/ProfessorResource.php
app/Actions/Scheduling/CreateProfessorAction.php
app/Actions/Scheduling/UpdateProfessorAction.php
app/Actions/Scheduling/DeleteProfessorAction.php  — retorna bool (protegido por FK)
app/Http/Controllers/Scheduling/ProfessorController.php
```

### Permisos (agregar a `database/data/permissions.yaml` y `roles.yaml`)

```
professors.view    professors.create    professors.update    professors.delete
```

### `ProfessorController@index` props

Además de `professors[]`, pasa `availableUsers[]`: usuarios activos con rol `Profesor` que aún no tienen registro en `professors`. Se usa en el modal de creación.

### `ProfessorResource` shape

```json
{
  "id": 1,
  "weeklyHourLimit": 20,
  "active": true,
  "user": {
    "id": 5,
    "name": "Carlos García",
    "email": "cgarcia@ejemplo.com"
  }
}
```

---

## Frontend

### Página

```
resources/js/pages/scheduling/Professors/Index.vue
```

- Tabla: Nombre del usuario · Email · Límite h/semana · Estado · Acciones.
- Al crear: `<select>` con `availableUsers` (usuarios con rol Profesor sin perfil aún).
- Modal de edición: permite cambiar `weekly_hour_limit` y `active`.
- Modal de eliminación: advertencia si tiene secciones/horarios (texto informativo).

### Archivos frontend

```
resources/js/types/scheduling.ts                                — añadir Professor, ProfessorCollection
resources/js/composables/forms/useProfessorForm.ts
resources/js/composables/permissions/useProfessorPermissions.ts
resources/js/components/scheduling/CreateProfessorModal.vue
resources/js/components/scheduling/EditProfessorModal.vue
resources/js/components/scheduling/DeleteProfessorModal.vue
```

### Sidebar

Añadir ítem **Profesores** al grupo "Horarios".

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/ProfessorControllerTest.php`:

- Admin puede listar profesores.
- Admin puede crear profesor (user con rol Profesor).
- Admin **no puede** crear profesor con usuario que no tiene rol Profesor → 422.
- Admin **no puede** crear dos profesores para el mismo usuario → 422 (unique).
- Admin puede actualizar límite de horas y estado activo.
- Admin puede eliminar profesor sin secciones ni horarios.
- Sin autenticación → redirect `/login`.
- Sin permiso → 403.
