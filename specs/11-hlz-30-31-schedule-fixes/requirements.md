# HLZ-30 + HLZ-31 + HLZ-32 — Schedule fixes: toast + migración down() + redirect period_id

## Contexto

Tres bugs descubiertos durante la auditoría QA del módulo de horarios (2026-05-30).
HLZ-32 fue descubierto al ejecutar HLZ-30: estaba enmascarado por el toast que nunca
aparecía (el test fallaba antes de llegar a la aserción de URL).
Los tres son fixes simples y acotados; se agrupan en un solo arnés.

---

## Bug 1 — HLZ-30: Toast de confirmación nunca aparece

### Síntoma
Después de crear, editar o eliminar un horario, la acción se ejecuta correctamente
(el horario aparece/desaparece en la grilla) pero el toast de confirmación nunca
se muestra al usuario.

### Causa raíz
`flashToast.ts` llama `toast.success()` importado de `vue-sonner`, pero el layout
`AppSidebarLayout.vue` monta `<Toast />` del sistema custom (`useToast.ts`), no un
`<Toaster>` de vue-sonner. Sin el `<Toaster>` de sonner montado, los toasts se
descartan silenciosamente.

### Fix acordado (Opción A)
En `flashToast.ts`, reemplazar `toast.success(data.message)` de `vue-sonner` por
la llamada al sistema custom (`useToast().toast({...})`), que sí está montado en
el layout. Eliminar el import de `vue-sonner`.

### Archivos afectados
- `resources/js/lib/flashToast.ts`

### Requisitos funcionales
- RF-01: Crear un horario válido muestra toast "Horario creado." ≥ 3 segundos.
- RF-02: Editar un horario válido muestra toast "Horario actualizado." ≥ 3 segundos.
- RF-03: Eliminar un horario muestra toast "Horario eliminado." ≥ 3 segundos.
- RF-04: Tests Dusk UC-H09, H10, H11, H28, H35, H36, H41 pasan en verde.

---

## Bug 2 — HLZ-31: Migración down() crashea con secciones escolares

### Síntoma
`php artisan migrate:refresh` (o `DatabaseMigrations` en tests Dusk) falla con
`SQLSTATE[23502]: Not null violation` cuando existen filas con `subject_id = NULL`
en la tabla `sections`.

### Causa raíz
El `down()` de `2026_04_29_131228_add_school_columns_to_sections_table.php` tiene:
```php
$table->foreignId('subject_id')->nullable(false)->change();
```
Las secciones escolares tienen `subject_id = NULL` por diseño correcto (en nivel
escolar la materia no es obligatoria en la sección). PostgreSQL rechaza el cambio
a NOT NULL cuando existen esas filas.

### Fix acordado
Eliminar esa línea del `down()`. `subject_id` era nullable antes de la migración;
no corresponde revertirlo a NOT NULL.

### Archivos afectados
- `database/migrations/2026_04_29_131228_add_school_columns_to_sections_table.php`
  — método `down()`

### Requisitos funcionales
- RF-05: `php artisan migrate:refresh` completa sin errores con secciones escolares.
- RF-06: Test Dusk UC-H24 pasa en verde.

---

## Bug 3 — HLZ-32: Redirect tras crear/editar/eliminar pierde el filtro period_id

### Síntoma
Después de crear, editar o eliminar un horario con el filtro `period_id` activo en
la URL, el redirect devuelve a la vista sin ese filtro — la URL queda solo con
`section_id` y `professor_id`.

### Causa raíz
El form de creación/edición/eliminación nunca incluye `period_id` en el body del
POST/PATCH/DELETE — ese valor solo estaba en la URL del GET inicial. El controller
hace `$request->only(['section_id', 'period_id', 'professor_id'])` para construir
el redirect, pero como `period_id` no viene en el request, queda ausente.

### Fix acordado
En `ScheduleController` (`store`, `update`, `destroy`), derivar el `period_id`
desde el `section_id` que sí viaja en el form. Una sección pertenece a exactamente
un período (`BelongsTo Period`), por lo que la FK `section.period_id` es única y
sin ambigüedad.

```php
$periodId = Section::find($request->input('section_id'))?->period_id;
return to_route('scheduling.schedules.index', array_filter([
    'section_id'   => $request->input('section_id'),
    'period_id'    => $periodId,
    'professor_id' => $request->input('professor_id'),
]));
```

### Archivos afectados
- `app/Http/Controllers/Scheduling/ScheduleController.php` — métodos `store`, `update`, `destroy`

### Requisitos funcionales
- RF-07: Crear un horario con `period_id` activo en URL → redirect incluye `period_id` en la URL.
- RF-08: Editar un horario con `period_id` activo en URL → redirect incluye `period_id`.
- RF-09: Eliminar un horario con `period_id` activo en URL → redirect incluye `period_id`.
- RF-10: Test Dusk UC-H10 pasa en verde.
