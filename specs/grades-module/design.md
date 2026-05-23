# Grades Module — Design

## Modelo de datos

### `grade_configs` — configuración institucional por nivel

```
id
level:           enum(primary_secondary, university)
period_id:       FK → periods (nullable) — null = global; not-null = override por período
scale_type:      enum(numeric, letter)
scale_min:       decimal nullable       — solo para numeric
scale_max:       decimal nullable       — solo para numeric
passing_value:   decimal                — mínimo aprobatorio en valor numérico siempre
timestamps
```

Índice único: `(level, period_id)` — una config activa por nivel por período.

### `grade_letter_values` — equivalencias para escala de letras

```
id
grade_config_id: FK → grade_configs (RESTRICT)
letter:          string     — A, B, C, D, E, F
numeric_equiv:   decimal    — valor numérico equivalente
is_passing:      boolean    — true = aprueba
sort_order:      integer    — 1 = mejor nota
timestamps
```

Solo existen si `grade_config.scale_type = 'letter'`.

### `grade_slots` — slots institucionales (Primer Parcial, Final, etc.)

```
id
grade_config_id: FK → grade_configs (RESTRICT)
name:            string    — definido por la institución
weight:          decimal   — porcentaje (todos los slots de un config suman 100)
sort_order:      integer
is_remedial:     boolean   — default false; true = slot de reparación
timestamps
```

### `grade_entries` — entradas del profesor (auto-referencial)

```
id
enrollment_detail_id: FK → enrollment_details (RESTRICT)
grade_slot_id:        FK → grade_slots (RESTRICT)
lapse_id:             FK → lapses (nullable, RESTRICT) — null = modo período; not-null = modo lapso
parent_id:            FK → grade_entries self (nullable, RESTRICT) — null = slot-level; not-null = sub-nota
name:                 string nullable    — solo sub-notas: nombre libre del profesor
weight:               decimal nullable   — solo sub-notas: peso dentro del slot (suma 100% por slot)
value:                decimal nullable   — la nota; null si tiene hijos (calculado de sub-notas)
is_published:         boolean            — false si modo manual y no publicado aún
timestamps
```

### `teams.grade_visibility` — configuración de visibilidad (columna nueva en teams)

```
grade_visibility: enum(real_time, manual) — default 'real_time'
```

---

## Arquitectura backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`

### Rutas

```
GET  /admin/grade-configs                           → Admin\GradeConfigController@index
GET  /admin/grade-configs/create                    → Admin\GradeConfigController@create
POST /admin/grade-configs                           → Admin\GradeConfigController@store
GET  /admin/grade-configs/{gradeConfig}/edit        → Admin\GradeConfigController@edit
PUT  /admin/grade-configs/{gradeConfig}             → Admin\GradeConfigController@update

GET  /professor/sections/{section}/grades           → Professor\GradeController@sheet
PUT  /professor/sections/{section}/grades/entries   → Professor\GradeController@upsertEntry
POST /professor/sections/{section}/grades/publish   → Professor\GradeController@publishSlot

GET  /student/grades                                → Student\GradeController@index
GET  /guardian/grades                               → Guardian\GradeController@index
```

### Actions

| Action | Responsabilidad |
|---|---|
| `CreateGradeConfigAction` | Crea `grade_config` + slots + letter values en transacción |
| `UpdateGradeConfigAction` | Si hay notas en el período, crea override por `period_id`; si no, edita global |
| `UpsertGradeEntryAction` | Crea o actualiza `grade_entry` (slot-level o sub-nota). Activa `CalculateSlotValueAction` si tiene padre |
| `CalculateSlotValueAction` | Recalcula `value` de la entrada padre: `Σ(sub.value × sub.weight / 100)` |
| `PublishGradeSlotAction` | Marca `is_published = true` para todas las entries de un slot en una sección |
| `EnableRemedialAction` | Habilita el slot `is_remedial` para un `EnrollmentDetail` específico |

### Resources

| Resource | Shape |
|---|---|
| `GradeConfigResource` | config + slots + letter values (para admin) |
| `SectionGradeSheetResource` | planilla: `{ slots[], students[{ name, entries_by_slot }] }` |
| `StudentGradeCardResource` | `{ subjects[{ name, slots[], final_grade, passed }] }` |

---

## Cálculo de notas

### Nota de un slot

- **Sin sub-notas** → `value` ingresado directamente por el profesor.
- **Con sub-notas** → `value = Σ(sub.value × sub.weight / 100)`, persistido en el padre por `CalculateSlotValueAction`.

### Nota definitiva del período (modo período — universitario)

```
definitiva = Σ(slot.value × slot.weight / 100)   [solo slots is_remedial = false]
```

### Nota definitiva del período (modo lapso — primaria/secundaria)

```
nota_lapso_N = Σ(slot.value × slot.weight / 100)   [para ese lapso]
definitiva   = promedio(nota_lapso_1, nota_lapso_2, ...) [igual peso entre lapsos]
```

### Reparación

Si el slot `is_remedial` está habilitado y tiene valor: `definitiva_final = max(definitiva, remedial.value)`.

---

## Arquitectura frontend

Patrón: `FormComposable → Page → PermissionComposable → Type`

### Tipos TypeScript

```
types/grade-config.ts  → GradeConfig, GradeSlot, GradeLetterValue, GradeScaleType
types/grade-entry.ts   → GradeEntry, SectionGradeSheet, StudentGradeCard, GradeVisibility
```

### Páginas y composables

**Admin**
```
pages/admin/GradeConfigs/Index.vue
pages/admin/GradeConfigs/Form.vue                   (create + edit compartido)
composables/forms/useGradeConfigForm.ts
composables/permissions/useGradeConfigPermissions.ts
```

**Profesor — planilla de notas**
```
pages/professor/Grades/Sheet.vue                    (tabla inline editable)
composables/forms/useGradeEntryForm.ts
```

La planilla del profesor: filas = estudiantes, columnas = slots institucionales. Cada celda es editable inline. Click en celda → edita nota directamente. Icono "subdividir" despliega panel lateral con sub-notas y pesos. En modo `manual`, botón "Publicar [Slot]" visible por columna.

**Estudiante / Representante**
```
pages/student/Grades/Index.vue
pages/guardian/Grades/Index.vue
```

Vista de solo lectura. Tarjetas por materia con slots visibles y nota definitiva. En modo `manual`, solo muestra entries `is_published = true`.

---

## Decisiones de diseño

1. **Enfoque B auto-referencial** para `grade_entries` — una sola tabla diferencia slot-level (`parent_id = null`) de sub-nota (`parent_id = entry_id`). Limpio y queryable.
2. **`passing_value` siempre numérico** — para escala de letras, el `passing_value` es el `numeric_equiv` de la letra mínima aprobatoria. Simplifica comparaciones.
3. **Lapsos de igual peso** en modo primaria/secundaria — los lapsos contribuyen igual a la definitiva. Configurable en una iteración futura si se necesita ponderar lapsos.
4. **`grade_visibility` en `Team`** — no hay tabla settings; se añade como columna al modelo Team. Si crece el número de settings, se refactoriza a tabla dedicada.
5. **Override por período es aditivo** — la config global no se modifica; se crea una nueva fila `grade_configs` con `period_id` explícito que tiene precedencia.
