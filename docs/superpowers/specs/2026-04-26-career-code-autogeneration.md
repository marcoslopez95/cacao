# Career Code Auto-Generation — Spec

**Goal:** El código de carrera se genera automáticamente al crear, usando las iniciales del nombre (omitiendo palabras de parada) más el ID. El usuario puede editarlo después.

---

## Regla de generación

```
code = {iniciales}-{ID}
```

- **Iniciales**: primera letra en mayúscula de cada palabra del nombre que NO esté en la lista de palabras de parada.
- **ID**: número de la carrera recién creada, con mínimo 2 dígitos (`str_pad($id, 2, '0', STR_PAD_LEFT)`).
- **Palabras de parada**: `['a', 'al', 'con', 'de', 'del', 'e', 'el', 'en', 'la', 'las', 'lo', 'los', 'o', 'para', 'por', 'sin', 'su', 'un', 'una', 'y']`

### Ejemplos

| Nombre | Iniciales | ID | Código |
|---|---|---|---|
| Ingeniería en Sistemas | IS | 1 | `IS-01` |
| Diseño Gráfico y Comunicación Visual | DGCV | 3 | `DGCV-03` |
| Contabilidad | C | 8 | `C-08` |
| Medicina | M | 12 | `M-12` |

### Longitud

El campo `code` es `string(10)`. La función trunca las iniciales si el total supera 10 caracteres (raro en nombres académicos reales).

---

## Flujo por operación

| Operación | Código |
|---|---|
| **Crear** | Auto-generado server-side. No aparece en el formulario de creación. |
| **Editar** | Campo editable, pre-relleno con el código generado. Validación: unique ignorando la carrera actual. |

---

## Cambios necesarios

### Backend

**Nueva migración** — `make_careers_code_nullable`:
- `code` pasa de `NOT NULL` a `NULLABLE` (se sigue requiriendo unique).
- Necesario porque el ID solo existe después del INSERT.

**`CreateCareerAction`** — lógica nueva:
1. `DB::transaction`: crea carrera sin `code`.
2. Genera código con `generateCode(name, id)`.
3. Actualiza `code` en el mismo registro.
4. Retorna la carrera.

Método privado `generateCode(string $name, int $id): string` vive en la action.

**`StoreCareerRequest`** — eliminar:
- Regla `code` de `rules()`
- Normalización de `code` en `prepareForValidation()`
- Mensaje de error de `code`

### Frontend

**`CreateCareerModal.vue`** — eliminar el campo `code` (label + input + InputError).

### Tests

**`CareerControllerTest.php`** — actualizar:
- Quitar `'code' => '...'` de los datos POST en store tests.
- Eliminar `test('store fails validation when code is duplicate')` (aplica solo a update).
- Eliminar `test('store normalizes code to uppercase')` (ya no aplica a store).
- Agregar `test('store auto-generates code from name and id')` que verifique el formato generado.

---

## Sin cambios

- `UpdateCareerRequest` — sigue validando `code` (único, ignorando carrera actual)
- `UpdateCareerAction` — sigue actualizando `code` desde el wrapper
- `EditCareerModal.vue` — sigue mostrando `code` editable
- `CareerWrapper::getCode()` — se usa solo en update
- `CareerResource` — sigue retornando `code`
