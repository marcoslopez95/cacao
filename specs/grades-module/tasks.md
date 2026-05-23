# Grades Module — Tasks

## Task 1 — Migraciones, modelos, enums y factories

**Objetivo:** infraestructura de datos completa para el módulo de notas.

### Migraciones (en orden)
1. `add_grade_visibility_to_teams_table` — columna `grade_visibility` enum(`real_time`, `manual`) default `real_time`
2. `create_grade_configs_table` — `level`, `period_id` FK nullable, `scale_type`, `scale_min`, `scale_max`, `passing_value`; unique(`level`, `period_id`)
3. `create_grade_letter_values_table` — `grade_config_id` FK RESTRICT, `letter`, `numeric_equiv`, `is_passing`, `sort_order`
4. `create_grade_slots_table` — `grade_config_id` FK RESTRICT, `name`, `weight`, `sort_order`, `is_remedial` default false
5. `create_grade_entries_table` — `enrollment_detail_id` FK RESTRICT, `grade_slot_id` FK RESTRICT, `lapse_id` FK nullable RESTRICT, `parent_id` FK self nullable RESTRICT, `name` nullable, `weight` nullable, `value` nullable, `is_published` boolean

### Enums
- `App\Enums\GradeLevel` — `PrimarySecondary`, `University`
- `App\Enums\GradeScaleType` — `Numeric`, `Letter`
- `App\Enums\GradeVisibility` — `RealTime`, `Manual`

### Modelos
- `GradeConfig` — casts para enums; relations: `slots()`, `letterValues()`, `period()`
- `GradeLetterValue` — relation: `config()`
- `GradeSlot` — relation: `config()`, `entries()`
- `GradeEntry` — casts; relations: `enrollmentDetail()`, `slot()`, `lapse()`, `parent()`, `children()`; scope `published()`
- Añadir a `Team`: cast `grade_visibility → GradeVisibility`
- Añadir a `EnrollmentDetail`: `gradeEntries()` hasMany

### Factories
- `GradeConfigFactory` — estados: `numeric()`, `letter()`, `university()`, `primarySecondary()`
- `GradeSlotFactory`
- `GradeLetterValueFactory`
- `GradeEntryFactory` — estados: `published()`, `unpublished()`, `subEntry()`

---

## Task 2 — CRUD de configuración de notas (Admin backend)

**Objetivo:** el admin puede crear y editar configuraciones de notas por nivel.

### Archivos
- `app/Policies/GradeConfigPolicy.php` — solo admin puede CRUD
- `app/Http/Requests/Admin/StoreGradeConfigRequest.php` — valida level, scale_type, slots (array, pesos suman 100, al menos 1 slot no-remedial)
- `app/Http/Requests/Admin/UpdateGradeConfigRequest.php`
- `app/Http/Wrappers/Admin/GradeConfigWrapper.php` — getters: `getLevel()`, `getScaleType()`, `getSlots()`, `getLetterValues()`, `getPeriodId()`
- `app/Actions/Admin/CreateGradeConfigAction.php` — crea config + slots + letter values en transacción
- `app/Actions/Admin/UpdateGradeConfigAction.php` — si hay `grade_entries` en el período activo, crea override; si no, edita in-place
- `app/Http/Resources/Admin/GradeConfigResource.php` — config + slots + letterValues
- `app/Http/Controllers/Admin/GradeConfigController.php` — `index`, `create`, `store`, `edit`, `update`
- Rutas en `routes/web.php` bajo prefix `/admin`

---

## Task 3 — Configuración de notas (Admin frontend)

**Objetivo:** interfaz para que el admin gestione la configuración de evaluación.

### Archivos
- `resources/js/types/grade-config.ts` — `GradeConfig`, `GradeSlot`, `GradeLetterValue`, `GradeScaleType`, `GradeLevel`
- `resources/js/composables/permissions/useGradeConfigPermissions.ts` — `canCreate`, `canEdit`
- `resources/js/composables/forms/useGradeConfigForm.ts` — `useForm` + Wayfinder, `addSlot()`, `removeSlot()`, `reorderSlots()`, `addLetterValue()`, validación suma 100%
- `resources/js/pages/admin/GradeConfigs/Index.vue` — lista de configs por nivel con botón editar
- `resources/js/pages/admin/GradeConfigs/Form.vue` — formulario compartido (crear/editar): selector de escala, builder de slots con nombre + peso + drag para reordenar, sección de letras si `scale_type = letter`

---

## Task 4 — Entrada de notas del profesor (backend)

**Objetivo:** el profesor puede cargar y gestionar notas de sus secciones.

### Archivos
- `app/Policies/GradeEntryPolicy.php` — solo el profesor dueño de la sección puede upsert/publish
- `app/Http/Requests/Professor/UpsertGradeEntryRequest.php` — valida `enrollment_detail_id`, `grade_slot_id`, `lapse_id`, `parent_id`, `value`, `name`, `weight`
- `app/Http/Wrappers/Professor/GradeEntryWrapper.php`
- `app/Actions/Professor/UpsertGradeEntryAction.php` — crea o actualiza entry; si `parent_id` not null, llama a `CalculateSlotValueAction`; respeta `grade_visibility` del team para setear `is_published`
- `app/Actions/Professor/CalculateSlotValueAction.php` — recalcula `value` del padre: `Σ(child.value × child.weight / 100)`; escribe en `parent.value`
- `app/Actions/Professor/PublishGradeSlotAction.php` — bulk update `is_published = true` para entries de un slot en una sección
- `app/Http/Resources/Professor/SectionGradeSheetResource.php` — `{ slots[], students[{ enrollment_detail_id, name, entries_by_slot_id }] }`
- `app/Http/Controllers/Professor/GradeController.php` — `sheet()` GET, `upsertEntry()` PUT, `publishSlot()` POST
- Rutas bajo prefix `/professor`

---

## Task 5 — Planilla de notas del profesor (frontend)

**Objetivo:** interfaz editable de notas por sección para el profesor.

### Archivos
- `resources/js/types/grade-entry.ts` — `GradeEntry`, `SectionGradeSheet`, `GradeSheetStudent`, `GradeSheetSlot`
- `resources/js/composables/forms/useGradeEntryForm.ts` — `upsertEntry()`, `addSubEntry()`, `removeSubEntry()`, `publishSlot()`, manejo de estado de celda (saving, error)
- `resources/js/pages/professor/Grades/Sheet.vue` — tabla sticky header: columnas = slots, filas = estudiantes; celda editable inline con debounce de guardado; panel lateral para sub-notas (nombre + peso + valor); badge "No publicado" por slot en modo manual; botón "Publicar" por columna

---

## Task 6 — Vista de notas estudiante/representante (backend)

**Objetivo:** exponer notas al estudiante y al representante (solo publicadas en modo manual).

### Archivos
- `app/Http/Resources/Student/GradeCardResource.php` — `{ period, subjects[{ name, lapse?, slots[{ name, value, is_published }], final_grade, passed }] }`
- `app/Http/Controllers/Student/GradeController.php` — `index()`: carga enrollment_details del estudiante autenticado con entries publicadas
- `app/Http/Controllers/Guardian/GradeController.php` — igual pero para el representado
- Rutas bajo `/student` y `/guardian`

---

## Task 7 — Vista de notas estudiante/representante (frontend)

**Objetivo:** interfaz de solo lectura para consultar notas.

### Archivos
- `resources/js/pages/student/Grades/Index.vue` — tarjetas por materia: slots con valores, nota definitiva destacada, badge Aprobado/Reprobado; si `is_published = false` → slot oculto en modo manual
- `resources/js/pages/guardian/Grades/Index.vue` — misma lógica con el representado

---

## Task 8 — Reparación (backend + frontend)

**Objetivo:** habilitar y gestionar nota de reparación por estudiante.

### Backend
- `app/Actions/Admin/EnableRemedialAction.php` — crea `grade_entry` con el slot `is_remedial` del config activo para el `enrollment_detail` dado
- `app/Http/Controllers/Admin/RemedialGradeController.php` — `store()`: habilita reparación para un enrollment_detail
- Lógica de nota definitiva con reparación: `final = max(definitiva_regular, remedial.value)` — implementar en `StudentGradeCardResource` y `GradeCardResource`

### Frontend
- Botón "Habilitar reparación" en planilla del profesor (visible solo si el estudiante reprobó y no tiene reparación aún)
- Celda de reparación aparece en planilla una vez habilitada
- En vista del estudiante, slot de reparación visible si existe

---

## Task 9 — Pest feature tests

**Objetivo:** cobertura de los flujos críticos del módulo.

### Tests a escribir
- `tests/Feature/Admin/GradeConfigTest.php` — CRUD configs, validación suma 100%, override por período, escala de letras
- `tests/Feature/Professor/GradeEntryTest.php` — cargar nota directa, sub-notas (cálculo automático del padre), visibilidad real_time vs manual, publicar slot
- `tests/Feature/Professor/GradeCalculationTest.php` — nota definitiva modo período, nota definitiva modo lapso, reparación eleva nota
- `tests/Feature/Student/GradeViewTest.php` — estudiante ve sus notas, modo manual oculta no publicadas, acceso denegado a notas de otros
- `tests/Feature/Guardian/GradeViewTest.php` — representante ve notas del representado, no de otros
