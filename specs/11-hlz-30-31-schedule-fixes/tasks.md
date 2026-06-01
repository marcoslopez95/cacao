# Tasks — HLZ-30 + HLZ-31

## Task 1 — Fix flashToast.ts (HLZ-30)

**Archivo:** `resources/js/lib/flashToast.ts`

Reemplazar el uso de `vue-sonner` por el sistema custom de toasts:
- Eliminar `import { toast } from 'vue-sonner'`
- Importar `useToast` del composable custom
- Cambiar `toast.success(data.message)` → llamada al sistema custom

Verificar que el toast custom acepta `type` y `message` (revisar `useToast.ts`
para ver la firma exacta).

**Criterio de aceptación:** RF-01, RF-02, RF-03 de requirements.md

---

## Task 2 — Fix migración down() (HLZ-31)

**Archivo:** `database/migrations/2026_04_29_131228_add_school_columns_to_sections_table.php`

En el método `down()`, eliminar la línea:
```php
$table->foreignId('subject_id')->nullable(false)->change();
```

**Criterio de aceptación:** RF-05 de requirements.md — `migrate:refresh` sin errores

---

## Task 3 — Fix ScheduleController redirect (HLZ-32)

**Archivo:** `app/Http/Controllers/Scheduling/ScheduleController.php`

En los tres métodos `store()`, `update()` y `destroy()`, reemplazar el redirect
actual por uno que derive `period_id` desde el `section_id` del request:

```php
$periodId = Section::find($request->input('section_id'))?->period_id;
return to_route('scheduling.schedules.index', array_filter([
    'section_id'   => $request->input('section_id'),
    'period_id'    => $periodId,
    'professor_id' => $request->input('professor_id'),
]));
```

Agregar `use App\Models\Section;` si no está importado.

**Criterio de aceptación:** RF-07, RF-08, RF-09 de requirements.md

---

## Task 4 — Verificar tests Dusk

Correr los tests afectados y confirmar que pasan:

```bash
vendor/bin/sail dusk tests/Browser/Academic/ScheduleCreateTest.php
vendor/bin/sail dusk tests/Browser/Academic/ScheduleConflictsTest.php
vendor/bin/sail dusk tests/Browser/Academic/ScheduleEditTest.php
vendor/bin/sail dusk tests/Browser/Academic/ScheduleDeleteTest.php
```

**Criterio de aceptación:** RF-04, RF-06, RF-10 — UC-H09, H10, H11, H24, H28, H35, H36, H41 en verde.

Correr también Pint:
```bash
vendor/bin/sail bin pint --dirty --format agent
```
