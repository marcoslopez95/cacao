# Tasks — HLZ-33 + HLZ-34

## Task 1 — Fix StoreScheduleRequest (HLZ-33)

Agregar a las reglas de `start_time` y `end_time`:
- `start_time`: añadir `'after_or_equal:07:00'`
- `end_time`: añadir `'before_or_equal:18:00'`

**Archivo:** `app/Http/Requests/Scheduling/StoreScheduleRequest.php`
**Criterio:** RF-01, RF-02, RF-03

---

## Task 2 — Fix UpdateScheduleRequest (HLZ-33)

Mismas reglas que Task 1.

**Archivo:** `app/Http/Requests/Scheduling/UpdateScheduleRequest.php`
**Criterio:** RF-01, RF-02, RF-03

---

## Task 3 — ProfessorHoursBar en EditScheduleModal (HLZ-34)

- Importar `ProfessorHoursBar` desde `@/components/scheduling/ProfessorHoursBar.vue`
- Agregar `import { computed } from 'vue'` si no está
- Agregar `computed selectedProfessor`: busca en `props.professors` por `form.value.professor_id`
- En el template, debajo del `<InputError>` del select de profesor, agregar:
  ```html
  <ProfessorHoursBar
      v-if="selectedProfessor"
      :current-hours="selectedProfessor.currentWeeklyHours"
      :limit-hours="selectedProfessor.weeklyHourLimit"
  />
  ```

**Archivo:** `resources/js/components/scheduling/EditScheduleModal.vue`
**Criterio:** RF-05, RF-06

---

## Task 4 — Verificar tests Dusk + Pint

```bash
vendor/bin/sail dusk tests/Browser/Academic/ScheduleCreateTest.php
vendor/bin/sail bin pint --dirty --format agent
```

**Criterio:** RF-04 (UC-H16, H17, H18) y RF-07 (UC-H19) en verde.
