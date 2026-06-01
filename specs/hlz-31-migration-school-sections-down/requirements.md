# HLZ-31 — Migration down() rompe DatabaseMigrations cuando existen school sections

## Problema

La migración `2026_04_29_131228_add_school_columns_to_sections_table` tiene
un bug en su método `down()` que impide el rollback cuando existen filas de
secciones escolares en la tabla `sections`.

**Causa raíz:**
- En `down()`, línea 51: `$table->foreignId('subject_id')->nullable(false)->change()`
- Las secciones escolares (`type = 'school'`) tienen `subject_id = NULL` (correcto,
  pues en nivel escolar el subject_id no es obligatorio)
- Al intentar hacer `subject_id NOT NULL`, PostgreSQL lanza:
  `SQLSTATE[23502]: Not null violation: column "subject_id" contains null values`
- Esto causa un crash en cualquier test que:
  1. Use `DatabaseMigrations` (que hace `migrate:refresh` en teardown)
  2. Cree al menos una Section de tipo School

**Evidencia:** test Dusk UC-H24 (`ScheduleCreateTest.php`) falla con
`QueryException: SQLSTATE[23502]: Not null violation` en el archivo de
migración.

## Requisitos funcionales

- RF-01: `php artisan migrate:refresh` completa sin errores cuando existen
  filas con `subject_id = NULL` en la tabla `sections`.
- RF-02: El test Dusk UC-H24 de `ScheduleCreateTest.php` pasa en verde.
- RF-03: El `down()` de la migración no debe intentar poner `subject_id` como
  NOT NULL si la columna fue diseñada para ser nullable en el contexto escolar.

## Solución propuesta

En el método `down()` de
`database/migrations/2026_04_29_131228_add_school_columns_to_sections_table.php`,
eliminar o corregir la línea que intenta revertir `subject_id` a NOT NULL:

```php
// INCORRECTO (línea actual):
$table->foreignId('subject_id')->nullable(false)->change();

// CORRECTO (subject_id ya era nullable antes — solo restaurar el unique index):
// No cambiar nullability; solo restaurar el índice unique original.
```

Alternativamente, si la intención es restaurar el estado anterior a la migración,
se debe primero eliminar las filas con `subject_id = NULL` o hacer la columna
nullable antes de revertir.

## Archivos afectados

- `database/migrations/2026_04_29_131228_add_school_columns_to_sections_table.php`
  — método `down()` línea 51
- Tests afectados: `tests/Browser/Academic/ScheduleCreateTest.php` (H24)
