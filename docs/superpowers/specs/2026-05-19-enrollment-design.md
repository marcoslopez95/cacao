# Spec: Módulo de Inscripción de Materias

**Fecha:** 2026-05-19
**Contexto:** Vista de estudiante — permite inscribir materias seleccionando una sección por materia para un período académico.

---

## Alcance

Implementar la página `pages/enrollment/Index.vue` con datos mock (sin backend por ahora). El backend se integrará en una iteración futura cuando existan los modelos `Enrollment`, `Section` y `Schedule`.

---

## Diseño de referencia

Fuente: `cacao/project/enrollment-*.jsx` + `enrollment-page.css` del bundle de handoff.

El diseño ya fue iterado y aprobado en Claude Design. Las decisiones de UX están fijas:
- Desktop: split horizontal (lista izquierda + panel sticky derecho)
- Móvil (< 820 px): cuadrícula de cards + summary strip abajo
- Switch automático por `matchMedia`

---

## Arquitectura — Enfoque B

```
pages/enrollment/
  Index.vue                      ← orquestación + layout + datos mock

components/enrollment/
  EnrollmentToolbar.vue          ← búsqueda + segmented type + chips de filtro
  EnrollmentMateriaRow.vue       ← fila acordeón de una materia
  EnrollmentSectionCard.vue      ← card de una sección dentro del acordeón
  EnrollmentSummaryPanel.vue     ← UC bar + stats + mini grid + acciones
  EnrollmentMiniGrid.vue         ← grilla semanal compacta (7→20h, 6 días)

composables/enrollment/
  useEnrollmentState.ts          ← selecciones, conflictos, resumen
  useEnrollmentFilters.ts        ← búsqueda + filtros (type, trim, prereqs, completadas)

types/enrollment.ts              ← todas las interfaces TypeScript
```

---

## Tipos TypeScript (`types/enrollment.ts`)

```ts
interface EnrollmentProfessor {
  id: string
  name: string
  initials: string
}

interface EnrollmentSlot {
  day: 0 | 1 | 2 | 3 | 4 | 5   // 0=Lun … 5=Sáb
  start: string                  // "07:00"
  end:   string                  // "09:00"
}

interface EnrollmentSection {
  code:        string            // "3-A"
  professor:   EnrollmentProfessor
  room:        string
  modality:    'Teórica' | 'Práctica' | 'Mixta' | 'Laboratorio' | 'Por definir'
  capacity:    number
  enrolled:    number
  slots:       EnrollmentSlot[]
  noSchedule?: boolean           // sección sin horario asignado aún
}

interface EnrollmentSubject {
  code:            string        // "ALG-302"
  name:            string
  credits:         number        // UC
  type:            'oblig' | 'electiva'
  recommendedTrim: boolean
  prereqsOk:       boolean
  completed:       boolean       // ya cursada y aprobada
  description:     string
  sections:        EnrollmentSection[]
}

interface EnrollmentRules {
  creditsMin:  number            // 12
  creditsMax:  number            // 24
  period:      string            // "2026-I"
  studentName: string
  studentCode: string
  career:      string
  trimester:   string
  deadline:    string            // "23 de mayo"
  daysLeft:    number            // 4
}

interface EnrollmentSummary {
  credits:   number
  hours:     number
  scheduled: number
  pending:   number
  count:     number
  items:     { subject: EnrollmentSubject; section: EnrollmentSection; sectionIdx: number }[]
}

type EnrollmentSelections = Record<string, number>  // subjectCode → sectionIdx
```

---

## Composable `useEnrollmentState`

```ts
// Input
selections: Ref<EnrollmentSelections>
setSelections: (fn: (prev) => EnrollmentSelections) => void

// Output
summary: ComputedRef<EnrollmentSummary>
findConflict(candidate: EnrollmentSection, ownCode: string): ConflictInfo | null
select(subjectCode: string, sectionIdx: number): void
unselect(subjectCode: string): void
creditsPct: ComputedRef<number>
creditsStatus: ComputedRef<'low' | 'ok' | 'high'>
```

`findConflict` es pura: compara slots del candidato contra cada sección ya seleccionada. Conflicto = mismo día + solapamiento de horas. Devuelve `{ subject, section, slotA, slotB }` o `null`.

---

## Composable `useEnrollmentFilters`

```ts
// Estado reactivo
search:          Ref<string>
typeFilter:      Ref<'all' | 'oblig' | 'electiva'>
recommendedOnly: Ref<boolean>
prereqsOnly:     Ref<boolean>
hideCompleted:   Ref<boolean>

// Output
filteredSubjects: ComputedRef<EnrollmentSubject[]>
```

Filtrado puro sobre el array de materias. Sin debounce (lista corta).

---

## Componentes

### `EnrollmentToolbar.vue`
Props: `modelValue` (search string) + `filters` object + `resultsCount`.
Emits: `update:modelValue`, `update:filters`.
UI: input de búsqueda con icono lupa + X, segmented control Todas/Obligatorias/Electivas, chips toggleables (Trimestre sugerido, Prereqs OK, Ocultar aprobadas), contador de resultados.

### `EnrollmentMateriaRow.vue`
Props: `subject`, `selections`, `expanded`.
Emits: `toggle`, `select(subjectCode, sectionIdx)`, `unselect(subjectCode)`, `ghost-enter(section)`, `ghost-leave`.
UI: fila clicable con chevron, código, nombre, UC, y badge de estado (bloqueada / sección elegida con horario / sin opciones / N secciones disponibles). Cuando `expanded`, renderiza `EnrollmentSectionCard` por cada sección en un grid.

### `EnrollmentSectionCard.vue`
Props: `section`, `sectionIdx`, `subjectCode`, `selectedIdx`, `conflict`.
Emits: `select`, `unselect`, `mouse-enter`, `mouse-leave`.
Estados:
- **selected**: borde acento + botón "Seleccionada / Quitar"
- **disabled-full**: cupos agotados
- **disabled-conflict**: choca con otra sección (tooltip con nombre de la materia conflictiva)
- **swap**: otra sección de la misma materia ya está seleccionada → "Cambiar a esta"
- **default**: "Seleccionar"

UI interna: código de sección + modalidad, avatar+nombre del profesor, slots (día + rango horario), sala con icono, barra de cupos con estado cromático (low/mid/high/full).

### `EnrollmentMiniGrid.vue`
Props: `selections`, `ghostCandidate?: { subjectCode, section }`.
Renderiza columnas Lun–Sáb, 07:00–20:00 a 30 px/hora. Slots posicionados absolute con `top` y `height` calculados. Ghost aparece rayado/semitransparente. Empty state si no hay selecciones.

### `EnrollmentSummaryPanel.vue`
Props: `summary`, `rules`, `creditsPct`, `creditsStatus`, `selections`, `ghost`.
Emits: `confirm`, `draft`.
UI: título + período, badge de UC (low/ok/high), barra de progreso con marcador mínimo, stats (materias, h/sem, con horario, por definir), `EnrollmentMiniGrid`, lista de pendientes sin horario, botones "Guardar borrador" + "Confirmar inscripción" (disabled si credits < min o > max), nota de alerta si faltan UC.

---

## Página `Index.vue`

```
<script setup lang="ts">
  // 1. defineProps<{ can: { enroll: boolean } }>()
  // 2. setLayoutProps({ breadcrumbs })
  // 3. const { filteredSubjects, search, typeFilter, ... } = useEnrollmentFilters(SUBJECTS)
  // 4. const selections = ref(INITIAL_SELECTIONS)
  // 5. const { summary, select, unselect, findConflict, creditsPct } = useEnrollmentState(selections)
  // 6. const expanded = ref(new Set<string>())
  // 7. const ghost = ref<GhostCandidate | null>(null)
  // 8. const isMobile = useMediaQuery('(max-width: 820px)')
</script>

<template>
  <!-- Page header: título, período, deadline -->
  <!-- EnrollmentToolbar -->
  <!-- Layout split (desktop) / cards (mobile) -->
  <!-- Panel izquierda: lista de EnrollmentMateriaRow -->
  <!-- Panel derecha: EnrollmentSummaryPanel (sticky) -->
</template>
```

Los datos mock viven en un archivo `composables/enrollment/enrollmentMockData.ts` para que en el futuro se reemplacen con los props de Inertia sin tocar los componentes.

---

## Datos mock

Copia fiel del `enrollment-data.jsx` del prototipo:
- 12 materias (ALG-302, BDA-301, CAL-301, ING-303, EST-302, UX-405, RED-401, IA-501, ETI-501, INV-401, ROB-401, PRG-202)
- Secciones con horarios reales (slots por día)
- 2 secciones sin horario (`noSchedule: true`): EST-302 3-B y INV-401 4-A
- 1 materia con prereqs pendientes: ROB-401
- 1 materia ya aprobada: PRG-202
- Selecciones iniciales: ALG-302 3-A, CAL-301 3-B, ING-303 3-A, INV-401 4-A

---

## Responsive

| Viewport | Layout |
|---|---|
| ≥ 820 px | Split: left 60% acordeón + right 40% panel sticky |
| < 820 px | Cards grid 1-col + summary strip colapsable abajo |

Se usa `useMediaQuery('(max-width: 820px)')` de `@vueuse/core` (ya está instalado).

---

## Colores por materia

Hash del código → paleta fija de 12 colores brand-aligned (terracota, verde, café, etc.). Implementado en `utils/enrollmentColor.ts` para reutilizarlo en grilla y cards.

---

## Fuera de alcance (esta iteración)

- Backend: ruta, controlador, FormRequest, Action, Resource
- Confirmación real al servidor
- Validación de período activo
- Guardado de borrador en base de datos
- Vista de confirmación final (paso 3 del wizard)
