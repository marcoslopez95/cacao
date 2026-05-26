# Requirements — user-edit-s01-identity-fix

**HLZs:** HLZ-27, HLZ-28  
**Auditoría:** QA Manager 2026-05-26  
**UCs relacionados:** UC-02 (actualizado), UC-35, UC-36, UC-38 en specs/qa/security/user-edit.md

---

## Contexto

El formulario de edición de usuario (S01 — Identidad personal) muestra campos editables para: documento, fecha de nacimiento, género, nacionalidad, teléfonos y foto. Todos tienen columna en `users`. Sin embargo:

1. **`UserEditResource`** no expone esas columnas — el frontend no puede pre-llenarlas.
2. **El handler 1** solo envía `first_name`, `last_name`, `email`, `roles` — las ediciones se pierden silenciosamente.
3. **Los catálogos** (`document_type_id`, `gender_id`, `nationality_id`) usan FKs enteras en DB pero el componente usaba strings estáticos — mismatch de tipos.

---

## Requisitos funcionales

### RF-01 — Carga pre-rellena campos de identidad extendida
Al cargar `/security/users/{id}/edit`, los campos de S01 deben llegar pre-poblados:
- `document_type_id` → select de tipo de documento muestra el tipo correcto
- `document_number` → input muestra el número
- `birth_date` → input fecha en formato YYYY-MM-DD
- `gender_id` → select de género muestra el correcto
- `nationality_id` → select de nacionalidad muestra el correcto
- `phone_primary` → input teléfono principal con número
- `phone_secondary` → input teléfono secundario con número (si existe)
- `profile_photo_url` → avatar muestra la foto (si existe)

### RF-02 — Guardar S01 persiste todos los campos
PATCH `/security/users/{user}/identity` actualiza la fila en `users` con:
- `first_name`, `last_name`, `email`, `roles` (ya funcionaba)
- `document_type_id`, `document_number`, `birth_date`, `gender_id`, `nationality_id`, `phone_primary`, `phone_secondary`

### RF-03 — Catálogos dinámicos en S01
`catalogData` incluye:
- `documentTypes`: `{ id, name, code }[]` desde `document_types` (active, ordered)
- `genders`: `{ id, name, code }[]` desde `genders` (active, ordered)
- `nationalities`: `{ id, name, iso2 }[]` desde `countries` (active, ordered by name)

Los selects de S01 usan estos catálogos con `:value="item.id"` (integer).

### RF-04 — Round-trip sin pérdida de datos
Un reload de la página tras guardar muestra los mismos valores que se guardaron.

### RF-05 — Campos sin cambiar no se destruyen
Si el usuario guarda S01 sin modificar `gender_id`, el valor existente en DB no cambia (no se nullifica por enviar el valor correcto del form state).

---

## Requisitos no funcionales

- Patrón `FormRequest → Controller → Wrapper → Action → Resource` obligatorio
- `UpdateUserRequest` sigue siendo la única validación de entrada — no duplicar validación en el controller
- `AppDocInput` recibe catálogo dinámico — se elimina dependencia a `UF_DOC_TYPES` en el componente
- Se corrige el bug del placeholder de `AppDocInput` (comparaba 'PAS' vs clave 'P')
- Tests de aceptación con Pest que cubren RF-01, RF-02, RF-04, RF-05
- `pint --dirty` después de cada cambio PHP
