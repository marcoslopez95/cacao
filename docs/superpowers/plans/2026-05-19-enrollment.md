# Enrollment Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the student enrollment page (`/enrollment`) with mock data — split layout on desktop, cards on mobile, with conflict detection, UC tracking, and a live mini weekly grid.

**Architecture:** Enfoque B — `pages/enrollment/Index.vue` orchestrates 5 sub-components from `components/enrollment/`, state lives in two composables (`useEnrollmentState`, `useEnrollmentFilters`), types in `types/enrollment.ts`, mock data in `composables/enrollment/enrollmentMockData.ts`. A minimal Laravel controller serves the Inertia page; Wayfinder auto-generates the route file.

**Tech Stack:** Vue 3 + TypeScript + Inertia v3 + Tailwind v4 + `@vueuse/core` (`useMediaQuery`) + `toMinutes` reused from `composables/scheduling/useScheduleLayout.ts` + Pest v4 for PHP route tests.

---

## File map

| Action | Path | Responsibility |
|---|---|---|
| Create | `resources/js/types/enrollment.ts` | All TypeScript interfaces |
| Create | `resources/js/composables/enrollment/enrollmentMockData.ts` | 12 mock subjects + rules + initial selections |
| Create | `resources/js/utils/enrollmentColor.ts` | Hash subject code → brand color |
| Create | `resources/js/composables/enrollment/useEnrollmentState.ts` | Selections, conflict detection, summary, credits % |
| Create | `resources/js/composables/enrollment/useEnrollmentFilters.ts` | Search + type/trim/prereqs/completed filters |
| Create | `resources/js/components/enrollment/EnrollmentMiniGrid.vue` | Compact 7→20h weekly grid with ghost preview |
| Create | `resources/js/components/enrollment/EnrollmentSectionCard.vue` | Section card with capacity bar + CTA states |
| Create | `resources/js/components/enrollment/EnrollmentMateriaRow.vue` | Accordion row for a subject |
| Create | `resources/js/components/enrollment/EnrollmentToolbar.vue` | Search + segmented type + filter chips |
| Create | `resources/js/components/enrollment/EnrollmentSummaryPanel.vue` | UC bar + stats + mini grid + confirm CTA |
| Create | `app/Http/Controllers/Enrollment/EnrollmentController.php` | Single `index()` → Inertia::render |
| Modify | `routes/web.php` | Add enrollment route under auth+verified |
| Run | `php artisan wayfinder:generate` | Auto-generate `resources/js/routes/enrollment/` |
| Create | `resources/js/pages/enrollment/Index.vue` | Page orchestration |
| Modify | `resources/js/components/AppSidebar.vue` | Add "Inscripción" nav item |
| Create | `tests/Feature/Enrollment/EnrollmentControllerTest.php` | Route access tests |

---

## Task 1 — TypeScript types

**Files:**
- Create: `resources/js/types/enrollment.ts`

- [ ] **Create the file**

```typescript
// resources/js/types/enrollment.ts

export type EnrollmentDay = 0 | 1 | 2 | 3 | 4 | 5

export interface EnrollmentProfessor {
    id: string
    name: string
    initials: string
}

export interface EnrollmentSlot {
    day: EnrollmentDay
    start: string // "07:00"
    end: string   // "09:00"
}

export interface EnrollmentSection {
    code: string
    professor: EnrollmentProfessor
    room: string
    modality: 'Teórica' | 'Práctica' | 'Mixta' | 'Laboratorio' | 'Por definir'
    capacity: number
    enrolled: number
    slots: EnrollmentSlot[]
    noSchedule?: boolean
}

export interface EnrollmentSubject {
    code: string
    name: string
    credits: number
    type: 'oblig' | 'electiva'
    recommendedTrim: boolean
    prereqsOk: boolean
    completed: boolean
    description: string
    sections: EnrollmentSection[]
}

export interface EnrollmentRules {
    creditsMin: number
    creditsMax: number
    period: string
    studentName: string
    studentCode: string
    career: string
    trimester: string
    deadline: string
    daysLeft: number
}

export interface EnrollmentSummaryItem {
    subject: EnrollmentSubject
    section: EnrollmentSection
    sectionIdx: number
}

export interface EnrollmentSummary {
    credits: number
    hours: number
    scheduled: number
    pending: number
    count: number
    items: EnrollmentSummaryItem[]
}

export interface EnrollmentConflict {
    subject: EnrollmentSubject
    section: EnrollmentSection
    slotA: EnrollmentSlot
    slotB: EnrollmentSlot
}

export type EnrollmentSelections = Record<string, number>  // subjectCode → sectionIdx

export interface EnrollmentFilters {
    type: 'all' | 'oblig' | 'electiva'
    recommendedOnly: boolean
    prereqsOnly: boolean
    hideCompleted: boolean
}

export interface EnrollmentGhostCandidate {
    subjectCode: string
    section: EnrollmentSection
}
```

- [ ] **Commit**

```bash
git add resources/js/types/enrollment.ts
git commit -m "feat(enrollment): add TypeScript types"
```

---

## Task 2 — Mock data + color utility

**Files:**
- Create: `resources/js/composables/enrollment/enrollmentMockData.ts`
- Create: `resources/js/utils/enrollmentColor.ts`

- [ ] **Create the color utility**

```typescript
// resources/js/utils/enrollmentColor.ts

const PALETTE = [
    '#C8521A', '#2E7D5C', '#7C5A3A', '#5B5A8A',
    '#A36B2D', '#1F5F8B', '#B12A1F', '#4C7A1F',
    '#6C4A7A', '#3C7A8B', '#A8511A', '#5B7A3A',
]

export function enrollmentColor(subjectCode: string): string {
    let hash = 0
    for (let i = 0; i < subjectCode.length; i++) {
        hash = ((hash * 31) + subjectCode.charCodeAt(i)) >>> 0
    }
    return PALETTE[hash % PALETTE.length]
}
```

- [ ] **Create the mock data file**

```typescript
// resources/js/composables/enrollment/enrollmentMockData.ts

import type {
    EnrollmentProfessor,
    EnrollmentSubject,
    EnrollmentRules,
    EnrollmentSelections,
} from '@/types/enrollment'

const PROFS: Record<string, EnrollmentProfessor> = {
    ms: { id: 'ms', name: 'Marco Salas',  initials: 'MS' },
    ap: { id: 'ap', name: 'Ana Pérez',    initials: 'AP' },
    lm: { id: 'lm', name: 'Luis Méndez',  initials: 'LM' },
    dq: { id: 'dq', name: 'Diana Quiroz', initials: 'DQ' },
    ht: { id: 'ht', name: 'Hugo Torres',  initials: 'HT' },
    ir: { id: 'ir', name: 'Iris Reyes',   initials: 'IR' },
    rn: { id: 'rn', name: 'Por asignar',  initials: '··' },
}

export const ENROLLMENT_RULES: EnrollmentRules = {
    creditsMin: 12,
    creditsMax: 24,
    period: '2026-I',
    studentName: 'Camila Ríos',
    studentCode: 'EST-2023-0418',
    career: 'Ing. en Sistemas',
    trimester: '5to trimestre',
    deadline: '23 de mayo',
    daysLeft: 4,
}

export const ENROLLMENT_SUBJECTS: EnrollmentSubject[] = [
    {
        code: 'ALG-302', name: 'Algoritmos y Estructuras', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Diseño y análisis de algoritmos, complejidad asintótica, estructuras avanzadas.',
        sections: [
            { code: '3-A', professor: PROFS.lm, room: 'Aula 201', modality: 'Teórica', capacity: 35, enrolled: 32,
              slots: [{ day: 0, start: '07:00', end: '09:00' }, { day: 2, start: '07:00', end: '09:00' }] },
            { code: '3-B', professor: PROFS.ms, room: 'Aula 105', modality: 'Teórica', capacity: 35, enrolled: 35,
              slots: [{ day: 0, start: '09:00', end: '11:00' }, { day: 2, start: '09:00', end: '11:00' }] },
            { code: '3-C', professor: PROFS.ap, room: 'Aula 203', modality: 'Teórica', capacity: 35, enrolled: 18,
              slots: [{ day: 1, start: '13:00', end: '15:00' }, { day: 3, start: '13:00', end: '15:00' }] },
        ],
    },
    {
        code: 'BDA-301', name: 'Bases de Datos I', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Modelo relacional, SQL, normalización, transacciones e índices.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 312', modality: 'Mixta', capacity: 30, enrolled: 28,
              slots: [{ day: 1, start: '14:00', end: '17:00' }, { day: 3, start: '14:00', end: '17:00' }] },
            { code: '4-B', professor: PROFS.ir, room: 'Lab 314', modality: 'Mixta', capacity: 30, enrolled: 22,
              slots: [{ day: 0, start: '14:00', end: '17:00' }, { day: 2, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'CAL-301', name: 'Cálculo III', credits: 5,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Cálculo vectorial, integrales múltiples, teoremas de Green y Stokes.',
        sections: [
            { code: '3-A', professor: PROFS.ap, room: 'Aula 105', modality: 'Teórica', capacity: 35, enrolled: 30,
              slots: [{ day: 0, start: '09:00', end: '11:00' }, { day: 2, start: '09:00', end: '11:00' }, { day: 4, start: '09:00', end: '10:00' }] },
            { code: '3-B', professor: PROFS.dq, room: 'Aula 220', modality: 'Teórica', capacity: 35, enrolled: 25,
              slots: [{ day: 1, start: '09:00', end: '12:00' }, { day: 3, start: '09:00', end: '11:00' }] },
        ],
    },
    {
        code: 'ING-303', name: 'Inglés Técnico III', credits: 2,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Lectura y redacción técnica en inglés para sistemas y desarrollo.',
        sections: [
            { code: '3-A', professor: PROFS.ir, room: 'Aula 302', modality: 'Teórica', capacity: 30, enrolled: 22,
              slots: [{ day: 2, start: '13:00', end: '15:00' }, { day: 4, start: '13:00', end: '15:00' }] },
            { code: '3-B', professor: PROFS.ir, room: 'Aula 304', modality: 'Teórica', capacity: 30, enrolled: 18,
              slots: [{ day: 1, start: '10:00', end: '12:00' }, { day: 3, start: '10:00', end: '12:00' }] },
        ],
    },
    {
        code: 'EST-302', name: 'Estadística Aplicada', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Inferencia, regresión, pruebas de hipótesis con ejemplos prácticos.',
        sections: [
            { code: '3-A', professor: PROFS.lm, room: 'Aula 215', modality: 'Teórica', capacity: 35, enrolled: 28,
              slots: [{ day: 1, start: '11:00', end: '13:00' }, { day: 3, start: '11:00', end: '13:00' }] },
            { code: '3-B', professor: PROFS.rn, room: 'Por definir', modality: 'Por definir', capacity: 35, enrolled: 0,
              noSchedule: true, slots: [] },
        ],
    },
    {
        code: 'UX-405', name: 'Diseño de Experiencia', credits: 3,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Investigación con usuarios, prototipado y pruebas de usabilidad.',
        sections: [
            { code: '5-A', professor: PROFS.ap, room: 'Aula 405', modality: 'Teórica', capacity: 30, enrolled: 24,
              slots: [{ day: 2, start: '10:00', end: '13:00' }] },
            { code: '5-B', professor: PROFS.ap, room: 'Aula 405', modality: 'Teórica', capacity: 30, enrolled: 30,
              slots: [{ day: 4, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'RED-401', name: 'Redes y Comunicaciones', credits: 4,
        type: 'oblig', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Modelo OSI, TCP/IP, ruteo, switching y fundamentos de seguridad de red.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 314', modality: 'Práctica', capacity: 30, enrolled: 24,
              slots: [{ day: 1, start: '15:00', end: '17:00' }, { day: 3, start: '15:00', end: '17:00' }] },
            { code: '4-B', professor: PROFS.lm, room: 'Lab 314', modality: 'Práctica', capacity: 30, enrolled: 18,
              slots: [{ day: 0, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'IA-501', name: 'Inteligencia Artificial', credits: 4,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Búsqueda, agentes, aprendizaje supervisado y no supervisado.',
        sections: [
            { code: '5-A', professor: PROFS.ht, room: 'Lab 315', modality: 'Mixta', capacity: 25, enrolled: 20,
              slots: [{ day: 4, start: '09:00', end: '11:00' }, { day: 5, start: '08:00', end: '10:00' }] },
        ],
    },
    {
        code: 'ETI-501', name: 'Ética Profesional', credits: 2,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Deontología, casos prácticos y dilemas profesionales en tecnología.',
        sections: [
            { code: '5-A', professor: PROFS.ir, room: 'Aula 110', modality: 'Teórica', capacity: 40, enrolled: 32,
              slots: [{ day: 0, start: '16:00', end: '18:00' }] },
            { code: '5-B', professor: PROFS.ir, room: 'Aula 110', modality: 'Teórica', capacity: 40, enrolled: 25,
              slots: [{ day: 4, start: '16:00', end: '18:00' }] },
        ],
    },
    {
        code: 'INV-401', name: 'Investigación de Operaciones', credits: 4,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Optimización lineal, redes y modelos de inventarios.',
        sections: [
            { code: '4-A', professor: PROFS.rn, room: 'Por definir', modality: 'Por definir', capacity: 30, enrolled: 0,
              noSchedule: true, slots: [] },
            { code: '4-B', professor: PROFS.dq, room: 'Aula 216', modality: 'Teórica', capacity: 30, enrolled: 15,
              slots: [{ day: 2, start: '14:00', end: '16:00' }, { day: 4, start: '14:00', end: '16:00' }] },
        ],
    },
    {
        code: 'ROB-401', name: 'Robótica', credits: 3,
        type: 'electiva', recommendedTrim: false, prereqsOk: false, completed: false,
        description: 'Cinemática, sensado y control de robots móviles.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 512', modality: 'Laboratorio', capacity: 20, enrolled: 12,
              slots: [{ day: 5, start: '08:00', end: '12:00' }] },
        ],
    },
    {
        code: 'PRG-202', name: 'Programación II', credits: 4,
        type: 'oblig', recommendedTrim: false, prereqsOk: true, completed: true,
        description: 'POO, manejo de memoria, patrones básicos de diseño.',
        sections: [],
    },
]

export const ENROLLMENT_INITIAL_SELECTIONS: EnrollmentSelections = {
    'ALG-302': 0,
    'CAL-301': 1,
    'ING-303': 0,
    'INV-401': 0,
}
```

- [ ] **Commit**

```bash
git add resources/js/composables/enrollment/enrollmentMockData.ts resources/js/utils/enrollmentColor.ts
git commit -m "feat(enrollment): add mock data and color utility"
```

---

## Task 3 — Composables: state + filters

**Files:**
- Create: `resources/js/composables/enrollment/useEnrollmentState.ts`
- Create: `resources/js/composables/enrollment/useEnrollmentFilters.ts`

- [ ] **Create `useEnrollmentState.ts`**

```typescript
// resources/js/composables/enrollment/useEnrollmentState.ts

import { computed } from 'vue'
import type { Ref } from 'vue'
import { toMinutes } from '@/composables/scheduling/useScheduleLayout'
import type {
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentSection,
    EnrollmentSlot,
    EnrollmentSummary,
    EnrollmentConflict,
} from '@/types/enrollment'

export function useEnrollmentState(
    subjects: EnrollmentSubject[],
    selections: Ref<EnrollmentSelections>,
    setSelections: (fn: (prev: EnrollmentSelections) => EnrollmentSelections) => void,
) {
    function slotsOverlap(a: EnrollmentSlot, b: EnrollmentSlot): boolean {
        if (a.day !== b.day) return false
        return toMinutes(a.start) < toMinutes(b.end) && toMinutes(b.start) < toMinutes(a.end)
    }

    function findConflict(
        candidate: EnrollmentSection,
        ownCode: string,
    ): EnrollmentConflict | null {
        if (!candidate.slots.length) return null
        for (const [subjectCode, secIdx] of Object.entries(selections.value)) {
            if (subjectCode === ownCode) continue
            const subject = subjects.find(s => s.code === subjectCode)
            const section = subject?.sections[secIdx]
            if (!section?.slots.length) continue
            for (const a of candidate.slots) {
                for (const b of section.slots) {
                    if (slotsOverlap(a, b)) {
                        return { subject: subject!, section, slotA: a, slotB: b }
                    }
                }
            }
        }
        return null
    }

    function slotHours(slots: EnrollmentSlot[]): number {
        return slots.reduce(
            (acc, s) => acc + (toMinutes(s.end) - toMinutes(s.start)) / 60,
            0,
        )
    }

    const summary = computed<EnrollmentSummary>(() => {
        let credits = 0, hours = 0, scheduled = 0, pending = 0
        const items = []
        for (const [code, secIdx] of Object.entries(selections.value)) {
            const subject = subjects.find(s => s.code === code)
            if (!subject) continue
            const section = subject.sections[secIdx]
            if (!section) continue
            credits += subject.credits
            hours += slotHours(section.slots)
            section.noSchedule ? pending++ : scheduled++
            items.push({ subject, section, sectionIdx: secIdx })
        }
        return { credits, hours, scheduled, pending, count: items.length, items }
    })

    const creditsPct = computed(() =>
        Math.min(100, (summary.value.credits / 24) * 100),
    )

    const creditsStatus = computed<'low' | 'ok' | 'high'>(() => {
        const c = summary.value.credits
        if (c > 24) return 'high'
        if (c < 12) return 'low'
        return 'ok'
    })

    function select(subjectCode: string, sectionIdx: number): void {
        setSelections(prev => ({ ...prev, [subjectCode]: sectionIdx }))
    }

    function unselect(subjectCode: string): void {
        setSelections(prev => {
            const next = { ...prev }
            delete next[subjectCode]
            return next
        })
    }

    return { summary, creditsPct, creditsStatus, findConflict, select, unselect }
}
```

- [ ] **Create `useEnrollmentFilters.ts`**

```typescript
// resources/js/composables/enrollment/useEnrollmentFilters.ts

import { computed, ref } from 'vue'
import type { EnrollmentSubject, EnrollmentFilters } from '@/types/enrollment'

export function useEnrollmentFilters(subjects: EnrollmentSubject[]) {
    const search = ref('')
    const filters = ref<EnrollmentFilters>({
        type: 'all',
        recommendedOnly: false,
        prereqsOnly: false,
        hideCompleted: true,
    })

    const filteredSubjects = computed(() =>
        subjects.filter(m => {
            if (m.completed && filters.value.hideCompleted) return false
            if (filters.value.type !== 'all' && m.type !== filters.value.type) return false
            if (filters.value.recommendedOnly && !m.recommendedTrim) return false
            if (filters.value.prereqsOnly && !m.prereqsOk) return false
            if (search.value) {
                const q = search.value.toLowerCase()
                if (!m.name.toLowerCase().includes(q) && !m.code.toLowerCase().includes(q)) {
                    return false
                }
            }
            return true
        }),
    )

    return { search, filters, filteredSubjects }
}
```

- [ ] **Commit**

```bash
git add resources/js/composables/enrollment/
git commit -m "feat(enrollment): add useEnrollmentState and useEnrollmentFilters composables"
```

---

## Task 4 — EnrollmentMiniGrid component

**Files:**
- Create: `resources/js/components/enrollment/EnrollmentMiniGrid.vue`

- [ ] **Create the component**

```vue
<!-- resources/js/components/enrollment/EnrollmentMiniGrid.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { toMinutes } from '@/composables/scheduling/useScheduleLayout'
import { enrollmentColor } from '@/utils/enrollmentColor'
import type {
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'

const HOUR_START = 7
const HOUR_END = 20
const PX_PER_HOUR = 30
const GRID_HEIGHT = (HOUR_END - HOUR_START) * PX_PER_HOUR
const DAY_ABBRS = ['L', 'M', 'X', 'J', 'V', 'S']
const DAYS = [0, 1, 2, 3, 4, 5]

const props = defineProps<{
    subjects: EnrollmentSubject[]
    selections: EnrollmentSelections
    ghostCandidate?: EnrollmentGhostCandidate | null
}>()

const hours = Array.from({ length: HOUR_END - HOUR_START + 1 }, (_, i) => HOUR_START + i)

function top(start: string): number {
    return ((toMinutes(start) - HOUR_START * 60) / 60) * PX_PER_HOUR
}

function height(start: string, end: string): number {
    return Math.max(4, ((toMinutes(end) - toMinutes(start)) / 60) * PX_PER_HOUR - 2)
}

interface MiniEvt {
    key: string
    subjectCode: string
    label: string
    day: number
    top: number
    height: number
    color: string
    ghost: boolean
}

const events = computed<MiniEvt[]>(() => {
    const result: MiniEvt[] = []
    for (const [code, secIdx] of Object.entries(props.selections)) {
        const subject = props.subjects.find(s => s.code === code)
        if (!subject) continue
        const section = subject.sections[secIdx]
        if (!section?.slots.length) continue
        for (const slot of section.slots) {
            result.push({
                key: `${code}-${slot.day}`,
                subjectCode: code,
                label: `${code.split('-')[0]} ${section.code}`,
                day: slot.day,
                top: top(slot.start),
                height: height(slot.start, slot.end),
                color: enrollmentColor(code),
                ghost: false,
            })
        }
    }
    if (props.ghostCandidate?.section.slots.length) {
        const g = props.ghostCandidate
        for (const slot of g.section.slots) {
            result.push({
                key: `ghost-${slot.day}`,
                subjectCode: g.subjectCode,
                label: `${g.subjectCode.split('-')[0]} ${g.section.code}`,
                day: slot.day,
                top: top(slot.start),
                height: height(slot.start, slot.end),
                color: enrollmentColor(g.subjectCode),
                ghost: true,
            })
        }
    }
    return result
})

const isEmpty = computed(
    () => Object.keys(props.selections).length === 0 && !props.ghostCandidate,
)
</script>

<template>
    <div class="enr-mini">
        <!-- Day headers -->
        <div class="enr-mini-head">
            <div class="enr-mini-gutter-spacer" />
            <div v-for="day in DAY_ABBRS" :key="day" class="enr-mini-day">{{ day }}</div>
        </div>

        <!-- Grid body -->
        <div class="enr-mini-body" :style="{ height: GRID_HEIGHT + 'px' }">
            <!-- Hour gutter -->
            <div class="enr-mini-gutter">
                <div
                    v-for="(h, i) in hours"
                    :key="h"
                    class="enr-mini-tick"
                    :style="{ top: i * PX_PER_HOUR - 6 + 'px' }"
                >
                    {{ String(h).padStart(2, '0') }}
                </div>
            </div>

            <!-- Day columns -->
            <div
                v-for="dayIdx in DAYS"
                :key="dayIdx"
                :class="['enr-mini-col', dayIdx === 5 ? 'enr-mini-col--weekend' : '']"
            >
                <div
                    v-for="(_, hi) in hours"
                    :key="hi"
                    class="enr-mini-hline"
                    :style="{ top: hi * PX_PER_HOUR + 'px' }"
                />
                <div
                    v-for="evt in events.filter(e => e.day === dayIdx)"
                    :key="evt.key"
                    :class="['enr-mini-evt', evt.ghost ? 'enr-mini-evt--ghost' : '']"
                    :style="{
                        top: evt.top + 'px',
                        height: evt.height + 'px',
                        '--enr-c': evt.color,
                    }"
                >
                    <span class="enr-mini-evt-label">{{ evt.label }}</span>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="isEmpty" class="enr-mini-empty">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>
            </svg>
            <p class="enr-mini-empty-title">Tu horario aparecerá aquí</p>
            <p class="enr-mini-empty-sub">Selecciona una sección para empezar</p>
        </div>
    </div>
</template>

<style>
.enr-mini { width: 100%; }

.enr-mini-head {
    display: grid;
    grid-template-columns: 20px repeat(6, 1fr);
    margin-bottom: 4px;
}
.enr-mini-gutter-spacer { width: 20px; }
.enr-mini-day {
    text-align: center;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
}

.enr-mini-body {
    display: grid;
    grid-template-columns: 20px repeat(6, 1fr);
    position: relative;
}
.enr-mini-gutter {
    position: relative;
    width: 20px;
}
.enr-mini-tick {
    position: absolute;
    right: 2px;
    font-size: 8px;
    font-family: var(--font-mono);
    color: var(--text-muted);
    line-height: 1;
}
.enr-mini-col {
    position: relative;
    border-left: 1px solid var(--border);
}
.enr-mini-col--weekend { opacity: 0.6; }
.enr-mini-hline {
    position: absolute;
    left: 0; right: 0;
    height: 1px;
    background: var(--border);
    pointer-events: none;
}
.enr-mini-evt {
    position: absolute;
    left: 2px; right: 2px;
    border-radius: 3px;
    background: color-mix(in srgb, var(--enr-c) 20%, var(--bg-surface));
    border-left: 3px solid var(--enr-c);
    padding: 2px 3px;
    overflow: hidden;
}
.enr-mini-evt--ghost {
    opacity: 0.45;
    background: repeating-linear-gradient(
        45deg,
        color-mix(in srgb, var(--enr-c) 15%, transparent),
        color-mix(in srgb, var(--enr-c) 15%, transparent) 3px,
        transparent 3px,
        transparent 7px
    );
    border-style: dashed;
}
.enr-mini-evt-label {
    font-size: 8px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.enr-mini-empty {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--text-muted);
    text-align: center;
    padding: 16px;
    background: var(--bg-surface);
    border: 1px dashed var(--border);
    border-radius: var(--radius-md);
}
.enr-mini-empty-title { font-size: 12px; font-weight: 500; color: var(--text-secondary); margin: 0; }
.enr-mini-empty-sub { font-size: 11px; color: var(--text-muted); margin: 0; }
</style>
```

- [ ] **Commit**

```bash
git add resources/js/components/enrollment/EnrollmentMiniGrid.vue
git commit -m "feat(enrollment): add EnrollmentMiniGrid component"
```

---

## Task 5 — EnrollmentSectionCard component

**Files:**
- Create: `resources/js/components/enrollment/EnrollmentSectionCard.vue`

- [ ] **Create the component**

```vue
<!-- resources/js/components/enrollment/EnrollmentSectionCard.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import { enrollmentColor } from '@/utils/enrollmentColor'
import type { EnrollmentSection, EnrollmentConflict } from '@/types/enrollment'

const DAY_ABBRS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const props = defineProps<{
    subjectCode: string
    section: EnrollmentSection
    sectionIdx: number
    selectedIdx: number | null
    conflict: EnrollmentConflict | null
}>()

const emit = defineEmits<{
    select: [sectionIdx: number]
    unselect: []
    mouseenter: []
    mouseleave: []
}>()

const color = computed(() => enrollmentColor(props.subjectCode))
const isSelf = computed(() => props.selectedIdx === props.sectionIdx)
const isOtherSelected = computed(() => props.selectedIdx != null && !isSelf.value)
const full = computed(() => props.section.enrolled >= props.section.capacity)
const disabled = computed(() => !isSelf.value && (full.value || !!props.conflict))

const fillPct = computed(() =>
    Math.min(100, (props.section.enrolled / props.section.capacity) * 100),
)
const fillState = computed(() => {
    const ratio = props.section.enrolled / props.section.capacity
    if (ratio >= 1) return 'full'
    if (ratio > 0.9) return 'high'
    if (ratio > 0.7) return 'mid'
    return 'low'
})

const cuposLeft = computed(() => props.section.capacity - props.section.enrolled)

const conflictTitle = computed(() => {
    if (!props.conflict) return ''
    const { subject, section, slotB } = props.conflict
    return `Choca con ${subject.code} (${section.code}) · ${DAY_ABBRS[slotB.day]} ${slotB.start.slice(0, 5)}–${slotB.end.slice(0, 5)}`
})
</script>

<template>
    <div
        :class="[
            'enr-sec-card',
            isSelf && 'enr-sec-card--selected',
            disabled && 'enr-sec-card--disabled',
            section.noSchedule && 'enr-sec-card--no-schedule',
        ]"
        :style="{ '--enr-c': color }"
        @mouseenter="!disabled && !isSelf && emit('mouseenter')"
        @mouseleave="emit('mouseleave')"
    >
        <!-- Header: code + modality -->
        <div class="enr-sec-card-head">
            <div class="enr-sec-card-code">
                <span class="enr-sec-card-dot" />
                <span>{{ section.code }}</span>
            </div>
            <span v-if="section.noSchedule" class="enr-sec-card-tag enr-sec-card-tag--warn">
                <AppIcon name="clock" :size="10" /> Horario por definir
            </span>
            <span v-else class="enr-sec-card-tag enr-sec-card-tag--muted">{{ section.modality }}</span>
        </div>

        <!-- Professor -->
        <div class="enr-sec-card-prof">
            <span class="enr-sec-card-avatar" :style="{ '--enr-c': color }">
                {{ section.professor.initials }}
            </span>
            <span class="enr-sec-card-prof-name">{{ section.professor.name }}</span>
        </div>

        <!-- Slots -->
        <div v-if="section.slots.length" class="enr-sec-card-slots">
            <span
                v-for="(slot, i) in section.slots"
                :key="i"
                class="enr-sec-card-slot"
            >
                <span class="day">{{ DAY_ABBRS[slot.day] }}</span>
                <span class="hrs">{{ slot.start.slice(0, 5) }}–{{ slot.end.slice(0, 5) }}</span>
            </span>
        </div>
        <div v-else class="enr-sec-card-slots enr-sec-card-slots--empty">
            <AppIcon name="info" :size="11" />
            La sección existe pero aún no tiene horario asignado.
        </div>

        <!-- Room -->
        <div class="enr-sec-card-meta">
            <AppIcon name="grid" :size="11" />
            <span>{{ section.room }}</span>
        </div>

        <!-- Capacity bar + CTA -->
        <div class="enr-sec-card-foot">
            <div class="enr-sec-card-cupos">
                <div class="enr-sec-card-bar">
                    <div
                        :class="['enr-sec-card-bar-fill', `enr-sec-card-bar-fill--${fillState}`]"
                        :style="{ width: fillPct + '%' }"
                    />
                </div>
                <div class="enr-sec-card-bar-lbl">
                    <strong>{{ section.enrolled }}</strong>/{{ section.capacity }}
                    <span class="hint">
                        {{ full ? 'cupos agotados' : `${cuposLeft} disponible${cuposLeft === 1 ? '' : 's'}` }}
                    </span>
                </div>
            </div>

            <div class="enr-sec-card-cta">
                <button
                    v-if="isSelf"
                    class="btn btn-sm enr-btn-selected"
                    @click="emit('unselect')"
                >
                    <AppIcon name="check" :size="12" /> Seleccionada
                    <span class="enr-btn-undo">Quitar</span>
                </button>
                <button
                    v-else-if="disabled"
                    class="btn btn-sm btn-secondary enr-btn-blocked"
                    disabled
                    :title="conflictTitle || 'Cupos agotados'"
                >
                    <AppIcon v-if="conflict" name="alert" :size="11" />
                    <AppIcon v-else name="x" :size="11" />
                    {{ conflict ? `Choca con ${conflict.subject.code}` : 'Sin cupos' }}
                </button>
                <button
                    v-else-if="isOtherSelected"
                    class="btn btn-sm btn-ghost"
                    @click="emit('select', sectionIdx)"
                >
                    <AppIcon name="arrowRight" :size="11" /> Cambiar a esta
                </button>
                <button
                    v-else
                    class="btn btn-sm btn-secondary"
                    @click="emit('select', sectionIdx)"
                >
                    Seleccionar
                </button>
            </div>
        </div>
    </div>
</template>

<style>
.enr-sec-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: border-color .15s, box-shadow .15s;
}
.enr-sec-card:hover:not(.enr-sec-card--disabled) {
    border-color: var(--border-strong);
}
.enr-sec-card--selected {
    border-color: var(--enr-c);
    background: color-mix(in srgb, var(--enr-c) 5%, var(--bg-surface));
    box-shadow: 0 0 0 1px var(--enr-c);
}
.enr-sec-card--disabled { opacity: 0.55; }

.enr-sec-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.enr-sec-card-code {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}
.enr-sec-card-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--enr-c);
    flex-shrink: 0;
}
.enr-sec-card-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: var(--radius-pill);
}
.enr-sec-card-tag--muted { background: var(--bg-sunken); color: var(--text-muted); }
.enr-sec-card-tag--warn { background: var(--warning-bg); color: var(--warning-fg); }

.enr-sec-card-prof {
    display: flex;
    align-items: center;
    gap: 8px;
}
.enr-sec-card-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--enr-c) 15%, var(--bg-sunken));
    color: var(--enr-c);
    font-size: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: var(--font-mono);
}
.enr-sec-card-prof-name { font-size: 12px; color: var(--text-secondary); }

.enr-sec-card-slots { display: flex; flex-wrap: wrap; gap: 4px; }
.enr-sec-card-slot {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 2px 7px;
    font-size: 11px;
}
.enr-sec-card-slot .day { font-weight: 600; color: var(--text-primary); }
.enr-sec-card-slot .hrs { color: var(--text-muted); font-family: var(--font-mono); }
.enr-sec-card-slots--empty {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--text-muted);
    font-style: italic;
}

.enr-sec-card-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--text-muted);
}

.enr-sec-card-foot { display: flex; flex-direction: column; gap: 8px; margin-top: 2px; }

.enr-sec-card-cupos { display: flex; flex-direction: column; gap: 4px; }
.enr-sec-card-bar {
    height: 4px;
    background: var(--bg-sunken);
    border-radius: 2px;
    overflow: hidden;
}
.enr-sec-card-bar-fill { height: 100%; border-radius: 2px; transition: width .3s; }
.enr-sec-card-bar-fill--low  { background: var(--success); }
.enr-sec-card-bar-fill--mid  { background: var(--warning); }
.enr-sec-card-bar-fill--high { background: var(--danger); }
.enr-sec-card-bar-fill--full { background: var(--danger); }
.enr-sec-card-bar-lbl {
    font-size: 11px;
    color: var(--text-muted);
    display: flex;
    gap: 4px;
}
.enr-sec-card-bar-lbl strong { color: var(--text-primary); }
.enr-sec-card-bar-lbl .hint { margin-left: auto; }

.enr-sec-card-cta .btn { width: 100%; justify-content: center; }

.enr-btn-selected {
    background: color-mix(in srgb, var(--enr-c) 12%, var(--bg-surface));
    border: 1px solid var(--enr-c);
    color: var(--enr-c);
    position: relative;
}
.enr-btn-selected:hover .enr-btn-undo { opacity: 1; }
.enr-btn-undo {
    opacity: 0;
    font-size: 11px;
    color: var(--danger);
    margin-left: auto;
    transition: opacity .15s;
}
.enr-btn-blocked { cursor: not-allowed !important; }
</style>
```

- [ ] **Commit**

```bash
git add resources/js/components/enrollment/EnrollmentSectionCard.vue
git commit -m "feat(enrollment): add EnrollmentSectionCard component"
```

---

## Task 6 — EnrollmentMateriaRow component

**Files:**
- Create: `resources/js/components/enrollment/EnrollmentMateriaRow.vue`

- [ ] **Create the component**

```vue
<!-- resources/js/components/enrollment/EnrollmentMateriaRow.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import EnrollmentSectionCard from '@/components/enrollment/EnrollmentSectionCard.vue'
import { enrollmentColor } from '@/utils/enrollmentColor'
import type {
    EnrollmentSubject,
    EnrollmentSelections,
    EnrollmentConflict,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'

const DAY_ABBRS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const props = defineProps<{
    subject: EnrollmentSubject
    selections: EnrollmentSelections
    expanded: boolean
    findConflict: (section: import('@/types/enrollment').EnrollmentSection, ownCode: string) => EnrollmentConflict | null
}>()

const emit = defineEmits<{
    toggle: []
    select: [subjectCode: string, sectionIdx: number]
    unselect: [subjectCode: string]
    'ghost-enter': [candidate: EnrollmentGhostCandidate]
    'ghost-leave': []
}>()

const color = computed(() => enrollmentColor(props.subject.code))
const selectedIdx = computed(() => props.selections[props.subject.code] ?? null)
const isSelected = computed(() => selectedIdx.value != null)
const blocked = computed(() => !props.subject.prereqsOk)

const selectedSection = computed(() =>
    isSelected.value ? props.subject.sections[selectedIdx.value!] : null,
)

const sectionStates = computed(() =>
    props.subject.sections.map((sec, i) => ({
        sec,
        i,
        conflict: props.findConflict(sec, props.subject.code),
    })),
)

const anyAvailable = computed(() =>
    sectionStates.value.some(({ sec, i, conflict }) =>
        selectedIdx.value === i || (!conflict && sec.enrolled < sec.capacity),
    ),
)

const statusText = computed(() => {
    if (blocked.value) return null
    if (isSelected.value && selectedSection.value) {
        const sec = selectedSection.value
        const slots = sec.noSchedule
            ? 'sin horario aún'
            : sec.slots.map(sl => `${DAY_ABBRS[sl.day]} ${sl.start.slice(0, 5)}`).join(', ')
        return `Sección ${sec.code} · ${slots}`
    }
    if (!anyAvailable.value) return 'Sin opciones libres ahora'
    const n = props.subject.sections.length
    return `${n} sección${n === 1 ? '' : 'es'} disponible${n === 1 ? '' : 's'}`
})
</script>

<template>
    <div
        :class="[
            'enr-mat-row',
            expanded && 'enr-mat-row--open',
            isSelected && 'enr-mat-row--selected',
            blocked && 'enr-mat-row--blocked',
        ]"
        :style="{ '--enr-c': color }"
    >
        <!-- Accordion trigger -->
        <button class="enr-mat-trigger" @click="emit('toggle')">
            <AppIcon
                :name="expanded ? 'chevronDown' : 'chevronRight'"
                :size="14"
                class="enr-mat-chevron"
            />
            <span class="enr-mat-code">{{ subject.code }}</span>
            <span class="enr-mat-name">
                {{ subject.name }}
                <span v-if="subject.type === 'electiva'" class="enr-mat-type-tag">Electiva</span>
            </span>
            <span class="enr-mat-uc">{{ subject.credits }} UC</span>
            <span class="enr-mat-status">
                <template v-if="blocked">
                    <span class="enr-status enr-status--blocked">
                        <AppIcon name="alert" :size="11" /> Prerequisitos pendientes
                    </span>
                </template>
                <template v-else-if="isSelected">
                    <span class="enr-status enr-status--selected">
                        <AppIcon name="check" :size="11" /> {{ statusText }}
                    </span>
                </template>
                <template v-else-if="!anyAvailable">
                    <span class="enr-status enr-status--warn">
                        <AppIcon name="alert" :size="11" /> {{ statusText }}
                    </span>
                </template>
                <template v-else>
                    <span class="enr-status enr-status--idle">{{ statusText }}</span>
                </template>
            </span>
        </button>

        <!-- Expanded body -->
        <Transition name="enr-expand">
            <div v-if="expanded" class="enr-mat-body">
                <p v-if="subject.description" class="enr-mat-desc">{{ subject.description }}</p>

                <div v-if="blocked" class="enr-mat-blocked-msg">
                    <AppIcon name="alert" :size="14" />
                    Esta materia tiene prerequisitos no cumplidos. Pasa por Coordinación para revisar tu plan.
                </div>
                <div v-else class="enr-sec-grid">
                    <EnrollmentSectionCard
                        v-for="({ sec, i, conflict }) in sectionStates"
                        :key="i"
                        :subject-code="subject.code"
                        :section="sec"
                        :section-idx="i"
                        :selected-idx="selectedIdx"
                        :conflict="conflict"
                        @select="(idx) => emit('select', subject.code, idx)"
                        @unselect="emit('unselect', subject.code)"
                        @mouseenter="emit('ghost-enter', { subjectCode: subject.code, section: sec })"
                        @mouseleave="emit('ghost-leave')"
                    />
                </div>
            </div>
        </Transition>
    </div>
</template>

<style>
.enr-mat-row {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    overflow: hidden;
    transition: border-color .15s;
}
.enr-mat-row + .enr-mat-row { margin-top: 6px; }
.enr-mat-row--selected { border-color: var(--enr-c); }
.enr-mat-row--blocked { opacity: 0.7; }

.enr-mat-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: transparent;
    border: 0;
    cursor: pointer;
    text-align: left;
    color: var(--text-primary);
    transition: background .1s;
}
.enr-mat-trigger:hover { background: var(--bg-surface-2); }

.enr-mat-chevron { color: var(--text-muted); flex-shrink: 0; transition: transform .15s; }
.enr-mat-row--open .enr-mat-chevron { color: var(--text-primary); }

.enr-mat-code {
    font-size: 11px;
    font-family: var(--font-mono);
    font-weight: 500;
    color: var(--text-muted);
    flex-shrink: 0;
    min-width: 70px;
}
.enr-mat-name {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}
.enr-mat-type-tag {
    font-size: 10px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: var(--radius-pill);
    background: var(--bg-sunken);
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.enr-mat-uc {
    font-size: 12px;
    font-family: var(--font-mono);
    font-weight: 600;
    color: var(--text-muted);
    flex-shrink: 0;
}
.enr-mat-status { flex-shrink: 0; font-size: 12px; }

.enr-status { display: inline-flex; align-items: center; gap: 4px; }
.enr-status--selected { color: var(--success); }
.enr-status--blocked  { color: var(--danger); }
.enr-status--warn     { color: var(--warning-fg); }
.enr-status--idle     { color: var(--text-muted); }

.enr-mat-body { padding: 0 12px 12px; }
.enr-mat-desc { font-size: 12px; color: var(--text-muted); margin: 0 0 10px; }
.enr-mat-blocked-msg {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: var(--danger-bg);
    color: var(--danger-fg);
    border-radius: var(--radius-sm);
    font-size: 13px;
}
.enr-sec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
}

/* Transition */
.enr-expand-enter-active, .enr-expand-leave-active {
    transition: max-height .2s ease, opacity .15s ease;
    overflow: hidden;
}
.enr-expand-enter-from, .enr-expand-leave-to { max-height: 0; opacity: 0; }
.enr-expand-enter-to, .enr-expand-leave-from { max-height: 1000px; opacity: 1; }
</style>
```

- [ ] **Commit**

```bash
git add resources/js/components/enrollment/EnrollmentMateriaRow.vue
git commit -m "feat(enrollment): add EnrollmentMateriaRow component"
```

---

## Task 7 — EnrollmentToolbar component

**Files:**
- Create: `resources/js/components/enrollment/EnrollmentToolbar.vue`

- [ ] **Create the component**

```vue
<!-- resources/js/components/enrollment/EnrollmentToolbar.vue -->
<script setup lang="ts">
import AppIcon from '@/components/UI/AppIcon.vue'
import type { EnrollmentFilters } from '@/types/enrollment'

const props = defineProps<{
    search: string
    filters: EnrollmentFilters
    resultsCount: number
}>()

const emit = defineEmits<{
    'update:search': [value: string]
    'update:filters': [value: EnrollmentFilters]
}>()

function setFilter<K extends keyof EnrollmentFilters>(key: K, value: EnrollmentFilters[K]) {
    emit('update:filters', { ...props.filters, [key]: value })
}

const TYPES = [
    { v: 'all'     as const, label: 'Todas' },
    { v: 'oblig'   as const, label: 'Obligatorias' },
    { v: 'electiva'as const, label: 'Electivas' },
]
</script>

<template>
    <div class="enr-toolbar">
        <!-- Search -->
        <div class="enr-search">
            <AppIcon name="search" :size="14" />
            <input
                :value="search"
                placeholder="Buscar materia o código…"
                @input="emit('update:search', ($event.target as HTMLInputElement).value)"
            />
            <button
                v-if="search"
                class="enr-search-clear"
                aria-label="Limpiar búsqueda"
                @click="emit('update:search', '')"
            >
                <AppIcon name="x" :size="11" />
            </button>
        </div>

        <!-- Type segmented -->
        <div class="enr-type-seg" role="radiogroup" aria-label="Tipo de materia">
            <button
                v-for="t in TYPES"
                :key="t.v"
                :class="['enr-type-seg-btn', filters.type === t.v && 'enr-type-seg-btn--active']"
                @click="setFilter('type', t.v)"
            >
                {{ t.label }}
            </button>
        </div>

        <!-- Filter chips -->
        <button
            :class="['enr-filter-chip', filters.recommendedOnly && 'enr-filter-chip--on']"
            @click="setFilter('recommendedOnly', !filters.recommendedOnly)"
        >
            <AppIcon name="calendar" :size="11" /> Trimestre sugerido
        </button>
        <button
            :class="['enr-filter-chip', filters.prereqsOnly && 'enr-filter-chip--on']"
            @click="setFilter('prereqsOnly', !filters.prereqsOnly)"
        >
            <AppIcon name="check" :size="11" /> Prereqs OK
        </button>
        <button
            :class="['enr-filter-chip', filters.hideCompleted && 'enr-filter-chip--on']"
            @click="setFilter('hideCompleted', !filters.hideCompleted)"
        >
            <AppIcon name="eye" :size="11" /> Ocultar aprobadas
        </button>

        <span class="enr-toolbar-count">{{ resultsCount }} materia{{ resultsCount === 1 ? '' : 's' }}</span>
    </div>
</template>

<style>
.enr-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
}

.enr-search {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 32px;
    padding: 0 10px;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    flex: 1 1 200px;
    color: var(--text-muted);
    transition: border-color .15s;
}
.enr-search:focus-within { border-color: var(--accent); }
.enr-search input {
    flex: 1;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--text-primary);
    font-size: 13px;
    font-family: var(--font-sans);
}
.enr-search-clear {
    background: transparent;
    border: 0;
    color: var(--text-muted);
    cursor: pointer;
    padding: 2px;
    border-radius: 50%;
    line-height: 0;
}
.enr-search-clear:hover { background: var(--bg-sunken); color: var(--text-primary); }

.enr-type-seg {
    display: inline-flex;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 2px;
    gap: 2px;
}
.enr-type-seg-btn {
    border: 0;
    background: transparent;
    height: 26px;
    padding: 0 11px;
    border-radius: 3px;
    font-size: 12px;
    font-family: var(--font-sans);
    color: var(--text-secondary);
    cursor: pointer;
    white-space: nowrap;
}
.enr-type-seg-btn:hover { color: var(--text-primary); }
.enr-type-seg-btn--active {
    background: var(--bg-surface);
    color: var(--text-primary);
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}

.enr-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    padding: 0 11px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: var(--radius-pill);
    font-size: 12px;
    font-family: var(--font-sans);
    color: var(--text-secondary);
    cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
    white-space: nowrap;
}
.enr-filter-chip:hover { border-color: var(--border-strong); color: var(--text-primary); }
.enr-filter-chip--on {
    background: var(--accent-soft);
    border-color: color-mix(in srgb, var(--accent) 30%, transparent);
    color: var(--accent);
}

.enr-toolbar-count { font-size: 12px; color: var(--text-muted); margin-left: auto; white-space: nowrap; }
</style>
```

- [ ] **Commit**

```bash
git add resources/js/components/enrollment/EnrollmentToolbar.vue
git commit -m "feat(enrollment): add EnrollmentToolbar component"
```

---

## Task 8 — EnrollmentSummaryPanel component

**Files:**
- Create: `resources/js/components/enrollment/EnrollmentSummaryPanel.vue`

- [ ] **Create the component**

```vue
<!-- resources/js/components/enrollment/EnrollmentSummaryPanel.vue -->
<script setup lang="ts">
import AppIcon from '@/components/UI/AppIcon.vue'
import EnrollmentMiniGrid from '@/components/enrollment/EnrollmentMiniGrid.vue'
import { enrollmentColor } from '@/utils/enrollmentColor'
import type {
    EnrollmentSummary,
    EnrollmentRules,
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'

const props = defineProps<{
    summary: EnrollmentSummary
    rules: EnrollmentRules
    creditsPct: number
    creditsStatus: 'low' | 'ok' | 'high'
    subjects: EnrollmentSubject[]
    selections: EnrollmentSelections
    ghost: EnrollmentGhostCandidate | null
    mobile?: boolean
}>()

const emit = defineEmits<{
    confirm: []
    draft: []
}>()

const minPct = (props.rules.creditsMin / props.rules.creditsMax) * 100
</script>

<template>
    <div :class="['enr-side', mobile && 'enr-side--mobile']">
        <!-- Header: title + UC badge -->
        <div class="enr-side-head">
            <div>
                <div class="enr-side-title">Tu inscripción</div>
                <div class="enr-side-sub">{{ rules.period }} · {{ rules.studentName }}</div>
            </div>
            <span :class="['enr-uc-badge', `enr-uc-badge--${creditsStatus}`]">
                <strong>{{ summary.credits }}</strong>
                <span>/ {{ rules.creditsMax }} UC</span>
            </span>
        </div>

        <!-- UC progress track -->
        <div class="enr-uc-track">
            <div class="enr-uc-track-fill" :style="{ width: creditsPct + '%', background: creditsStatus === 'low' ? 'var(--warning)' : 'var(--accent)' }" />
            <div class="enr-uc-track-min" :style="{ left: minPct + '%' }" :title="`Mínimo ${rules.creditsMin} UC`" />
            <span class="enr-uc-track-lbl">Mín {{ rules.creditsMin }} · Máx {{ rules.creditsMax }}</span>
        </div>

        <!-- Stats row -->
        <div class="enr-side-stats">
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.count }}</div>
                <div class="enr-side-stat-l">materia{{ summary.count === 1 ? '' : 's' }}</div>
            </div>
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.hours }}<span class="unit">h</span></div>
                <div class="enr-side-stat-l">por semana</div>
            </div>
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.scheduled }}</div>
                <div class="enr-side-stat-l">con horario</div>
            </div>
            <div v-if="summary.pending > 0" class="enr-side-stat enr-side-stat--warn">
                <div class="enr-side-stat-v">{{ summary.pending }}</div>
                <div class="enr-side-stat-l">por definir</div>
            </div>
        </div>

        <!-- Mini week grid -->
        <div class="enr-side-grid">
            <EnrollmentMiniGrid
                :subjects="subjects"
                :selections="selections"
                :ghost-candidate="ghost"
            />
        </div>

        <!-- Pending list (no-schedule sections) -->
        <div v-if="summary.pending > 0" class="enr-side-pending">
            <div class="enr-side-pending-head">
                <AppIcon name="clock" :size="12" /> Sin horario asignado
            </div>
            <div
                v-for="it in summary.items.filter(i => i.section.noSchedule)"
                :key="it.subject.code"
                class="enr-side-pending-row"
                :style="{ '--enr-c': enrollmentColor(it.subject.code) }"
            >
                <span class="enr-side-pending-dot" />
                <span class="enr-side-pending-t">{{ it.subject.code }} · {{ it.section.code }}</span>
                <span class="enr-side-pending-s">Se publicará cuando se asigne profesor y aula</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="enr-side-actions">
            <button class="btn btn-ghost btn-sm" @click="emit('draft')">
                Guardar borrador
            </button>
            <button
                class="btn btn-primary"
                :disabled="summary.credits < rules.creditsMin || summary.credits > rules.creditsMax"
                @click="emit('confirm')"
            >
                <AppIcon name="check" :size="14" /> Confirmar inscripción
            </button>
        </div>
        <p v-if="summary.credits < rules.creditsMin" class="enr-side-note">
            Te faltan <strong>{{ rules.creditsMin - summary.credits }} UC</strong> para alcanzar el mínimo.
        </p>
        <p v-if="summary.credits > rules.creditsMax" class="enr-side-note enr-side-note--danger">
            Superás el máximo de <strong>{{ rules.creditsMax }} UC</strong>.
        </p>
    </div>
</template>

<style>
.enr-side {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: sticky;
    top: 16px;
}
.enr-side--mobile { position: static; border-radius: var(--radius-md); }

.enr-side-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.enr-side-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }
.enr-side-sub { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 2px; }

.enr-uc-badge {
    display: flex;
    align-items: baseline;
    gap: 3px;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    font-size: 13px;
    flex-shrink: 0;
}
.enr-uc-badge strong { font-weight: 700; font-family: var(--font-mono); font-size: 16px; }
.enr-uc-badge span { font-size: 11px; color: var(--text-muted); }
.enr-uc-badge--low { background: var(--warning-bg); color: var(--warning-fg); }
.enr-uc-badge--ok  { background: var(--success-bg); color: var(--success-fg); }
.enr-uc-badge--high{ background: var(--danger-bg);  color: var(--danger-fg); }

.enr-uc-track {
    position: relative;
    height: 6px;
    background: var(--bg-sunken);
    border-radius: 3px;
    overflow: visible;
}
.enr-uc-track-fill { height: 100%; border-radius: 3px; transition: width .3s; }
.enr-uc-track-min {
    position: absolute;
    top: -3px;
    width: 2px;
    height: 12px;
    background: var(--text-muted);
    border-radius: 1px;
    transform: translateX(-50%);
}
.enr-uc-track-lbl {
    position: absolute;
    top: 10px;
    right: 0;
    font-size: 10px;
    color: var(--text-muted);
    font-family: var(--font-mono);
    white-space: nowrap;
}

.enr-side-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    text-align: center;
}
.enr-side-stat { }
.enr-side-stat--warn .enr-side-stat-v { color: var(--warning-fg); }
.enr-side-stat-v {
    font-size: 20px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--text-primary);
    line-height: 1;
}
.enr-side-stat-v .unit { font-size: 13px; font-weight: 400; }
.enr-side-stat-l { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

.enr-side-grid { }

.enr-side-pending {
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.enr-side-pending-head {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.enr-side-pending-row {
    display: grid;
    grid-template-columns: 10px 1fr;
    grid-template-rows: auto auto;
    column-gap: 8px;
    row-gap: 1px;
}
.enr-side-pending-dot {
    grid-row: 1 / 3;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--enr-c);
    margin-top: 3px;
}
.enr-side-pending-t { font-size: 12px; font-weight: 500; color: var(--text-primary); }
.enr-side-pending-s { font-size: 11px; color: var(--text-muted); }

.enr-side-actions { display: flex; gap: 8px; }
.enr-side-actions .btn-primary { flex: 1; justify-content: center; }

.enr-side-note {
    font-size: 12px;
    color: var(--warning-fg);
    margin: 0;
    padding: 6px 10px;
    background: var(--warning-bg);
    border-radius: var(--radius-sm);
}
.enr-side-note--danger { color: var(--danger-fg); background: var(--danger-bg); }
.enr-side-note strong { font-weight: 600; }
</style>
```

- [ ] **Commit**

```bash
git add resources/js/components/enrollment/EnrollmentSummaryPanel.vue
git commit -m "feat(enrollment): add EnrollmentSummaryPanel component"
```

---

## Task 9 — Laravel controller + route + wayfinder

**Files:**
- Create: `app/Http/Controllers/Enrollment/EnrollmentController.php`
- Modify: `routes/web.php`
- Run wayfinder

- [ ] **Create the controller**

```bash
php artisan make:controller Enrollment/EnrollmentController --no-interaction
```

Replace the generated file content with:

```php
<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('enrollment/Index');
    }
}
```

- [ ] **Add route to `routes/web.php`**

Add after the existing `Route::middleware(['auth', 'verified'])->prefix('security')...` block:

```php
Route::middleware(['auth', 'verified'])->prefix('enrollment')->name('enrollment.')->group(function () {
    Route::get('/', [App\Http\Controllers\Enrollment\EnrollmentController::class, 'index'])->name('index');
});
```

Also add the import at the top of the file (with the other use statements):

```php
use App\Http\Controllers\Enrollment\EnrollmentController;
```

And update the route to use the imported class:

```php
Route::middleware(['auth', 'verified'])->prefix('enrollment')->name('enrollment.')->group(function () {
    Route::get('/', [EnrollmentController::class, 'index'])->name('index');
});
```

- [ ] **Generate wayfinder routes**

```bash
vendor/bin/sail artisan wayfinder:generate
```

Expected: creates `resources/js/routes/enrollment/index.ts` with an `index` export.

- [ ] **Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Commit**

```bash
git add app/Http/Controllers/Enrollment/EnrollmentController.php routes/web.php resources/js/routes/enrollment/
git commit -m "feat(enrollment): add EnrollmentController and route"
```

---

## Task 10 — Index.vue page

**Files:**
- Create: `resources/js/pages/enrollment/Index.vue`

- [ ] **Create the page**

```vue
<!-- resources/js/pages/enrollment/Index.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { useMediaQuery } from '@vueuse/core'
import AppIcon from '@/components/UI/AppIcon.vue'
import EnrollmentToolbar from '@/components/enrollment/EnrollmentToolbar.vue'
import EnrollmentMateriaRow from '@/components/enrollment/EnrollmentMateriaRow.vue'
import EnrollmentSummaryPanel from '@/components/enrollment/EnrollmentSummaryPanel.vue'
import { useEnrollmentState } from '@/composables/enrollment/useEnrollmentState'
import { useEnrollmentFilters } from '@/composables/enrollment/useEnrollmentFilters'
import {
    ENROLLMENT_SUBJECTS,
    ENROLLMENT_RULES,
    ENROLLMENT_INITIAL_SELECTIONS,
} from '@/composables/enrollment/enrollmentMockData'
import { index as enrollmentIndex } from '@/routes/enrollment'
import type { EnrollmentSelections, EnrollmentGhostCandidate } from '@/types/enrollment'

setLayoutProps({
    breadcrumbs: [
        { title: 'Estudiante', href: '#' },
        { title: 'Inscripción', href: enrollmentIndex.url() },
    ],
})

const isMobile = useMediaQuery('(max-width: 820px)')

// State
const selections = ref<EnrollmentSelections>({ ...ENROLLMENT_INITIAL_SELECTIONS })
const expanded = ref(new Set<string>())
const ghost = ref<EnrollmentGhostCandidate | null>(null)

// Composables
const { search, filters, filteredSubjects } = useEnrollmentFilters(ENROLLMENT_SUBJECTS)
const { summary, creditsPct, creditsStatus, findConflict, select, unselect } =
    useEnrollmentState(
        ENROLLMENT_SUBJECTS,
        selections,
        fn => { selections.value = fn(selections.value) },
    )

function toggleExpanded(code: string): void {
    const next = new Set(expanded.value)
    if (next.has(code)) {
        next.delete(code)
    } else {
        next.add(code)
    }
    expanded.value = next
}

function handleConfirm(): void {
    // TODO: submit to backend when EnrollmentController::store is implemented
    alert('Inscripción confirmada (demo)')
}
</script>

<template>
    <Head title="Inscripción" />

    <!-- Page header -->
    <div class="enr-page-head">
        <div class="enr-page-head-l">
            <span class="enr-page-eyebrow">Período {{ ENROLLMENT_RULES.period }} · {{ ENROLLMENT_RULES.trimester }}</span>
            <h1 class="enr-page-title">Inscripción de materias</h1>
            <p class="enr-page-sub">
                Selecciona las materias y secciones que cursarás este trimestre.
                Solo puedes elegir <strong>una sección por materia</strong>.
            </p>
        </div>
        <div class="enr-page-head-r">
            <div class="enr-deadline">
                <AppIcon name="clock" :size="13" />
                <div>
                    <div class="enr-deadline-t">Cierra el <strong>{{ ENROLLMENT_RULES.deadline }}</strong></div>
                    <div class="enr-deadline-s">faltan {{ ENROLLMENT_RULES.daysLeft }} días</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout -->
    <div :class="['enr-layout', isMobile ? 'enr-layout--mobile' : 'enr-layout--split']">
        <!-- Main panel -->
        <div class="enr-main">
            <EnrollmentToolbar
                :search="search"
                :filters="filters"
                :results-count="filteredSubjects.length"
                @update:search="v => search = v"
                @update:filters="v => filters = v"
            />

            <!-- Subject list -->
            <div class="enr-list" role="list">
                <div v-if="filteredSubjects.length === 0" class="enr-empty">
                    <AppIcon name="search" :size="20" />
                    <div class="enr-empty-t">Sin resultados</div>
                    <div class="enr-empty-s">Prueba con otros filtros o limpia la búsqueda.</div>
                </div>
                <EnrollmentMateriaRow
                    v-for="subject in filteredSubjects"
                    :key="subject.code"
                    :subject="subject"
                    :selections="selections"
                    :expanded="expanded.has(subject.code)"
                    :find-conflict="findConflict"
                    @toggle="toggleExpanded(subject.code)"
                    @select="(code, idx) => select(code, idx)"
                    @unselect="(code) => unselect(code)"
                    @ghost-enter="g => ghost = g"
                    @ghost-leave="ghost = null"
                />
            </div>
        </div>

        <!-- Summary panel (desktop: sticky aside / mobile: below) -->
        <aside class="enr-aside">
            <EnrollmentSummaryPanel
                :summary="summary"
                :rules="ENROLLMENT_RULES"
                :credits-pct="creditsPct"
                :credits-status="creditsStatus"
                :subjects="ENROLLMENT_SUBJECTS"
                :selections="selections"
                :ghost="isMobile ? null : ghost"
                :mobile="isMobile"
                @confirm="handleConfirm"
                @draft="() => {}"
            />
        </aside>
    </div>
</template>

<style>
/* Page header */
.enr-page-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 16px;
}
.enr-page-eyebrow {
    display: inline-block;
    font-family: var(--font-mono);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--accent);
    margin-bottom: 6px;
}
.enr-page-title {
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.02em;
    margin: 0 0 4px;
    color: var(--text-primary);
}
.enr-page-sub {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
    max-width: 60ch;
}
.enr-deadline {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: var(--accent-soft);
    border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
    border-radius: var(--radius-md);
}
.enr-deadline-t { font-size: 13px; color: var(--text-primary); }
.enr-deadline-s { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }

/* Layout: split (desktop) */
.enr-layout { display: flex; gap: 20px; align-items: flex-start; }
.enr-layout--split .enr-main { flex: 1; min-width: 0; }
.enr-layout--split .enr-aside { width: 300px; flex-shrink: 0; }

/* Layout: mobile */
.enr-layout--mobile { flex-direction: column; }
.enr-layout--mobile .enr-main { width: 100%; }
.enr-layout--mobile .enr-aside { width: 100%; }

/* Subject list */
.enr-list { display: flex; flex-direction: column; }

/* Empty state */
.enr-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 40px 20px;
    color: var(--text-muted);
    text-align: center;
}
.enr-empty-t { font-size: 14px; font-weight: 500; color: var(--text-secondary); }
.enr-empty-s { font-size: 13px; }

@media (max-width: 820px) {
    .enr-page-head { flex-direction: column; align-items: flex-start; }
    .enr-page-title { font-size: 20px; }
}
</style>
```

- [ ] **Commit**

```bash
git add resources/js/pages/enrollment/Index.vue
git commit -m "feat(enrollment): add enrollment Index page"
```

---

## Task 11 — Add nav item to AppSidebar

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Import the enrollment route**

At the top of the `<script setup>` section, after the existing imports, add:

```typescript
import { index as enrollmentIndex } from '@/routes/enrollment'
```

- [ ] **Add the nav group**

In the `navGroups` computed, add a new group for the student portal. Insert it after the "General" group (after the `items: [{ icon: 'grid', ... }]` block, before the `if (page.props.auth?.permissions...)` check):

```typescript
groups.push({
    label: 'Estudiante',
    items: [
        { icon: 'edit', label: 'Inscripción', href: enrollmentIndex.url() },
    ],
})
```

- [ ] **Verify the active state works**

The existing `isActive()` function checks `currentUrl.value === href || currentUrl.value.startsWith(href + '/')`. The `/enrollment` path will match `/enrollment` exactly. No changes needed.

- [ ] **Commit**

```bash
git add resources/js/components/AppSidebar.vue
git commit -m "feat(enrollment): add Inscripción nav item to sidebar"
```

---

## Task 12 — Pest route tests

**Files:**
- Create: `tests/Feature/Enrollment/EnrollmentControllerTest.php`

- [ ] **Create test**

```bash
vendor/bin/sail artisan make:test --pest Enrollment/EnrollmentControllerTest
```

Replace the generated file content with:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user is redirected to login', function () {
    $this->withoutVite();

    $this->get('/enrollment')
        ->assertRedirect('/login');
});

test('authenticated unverified user is redirected to verify email', function () {
    $this->withoutVite();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertRedirect();
});

test('authenticated verified user can access enrollment page', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrollment/Index'));
});
```

- [ ] **Run the tests**

```bash
vendor/bin/sail artisan test --compact --filter=EnrollmentControllerTest
```

Expected output: 3 tests passing.

- [ ] **Commit**

```bash
git add tests/Feature/Enrollment/EnrollmentControllerTest.php
git commit -m "test(enrollment): add EnrollmentController route tests"
```

---

## Task 13 — Build and smoke test

- [ ] **TypeScript check**

```bash
vendor/bin/sail npm run build
```

Expected: no TypeScript errors, build succeeds.

- [ ] **Run all tests**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all existing tests + 3 new enrollment tests passing.

- [ ] **Open in browser and verify**

```bash
vendor/bin/sail artisan serve
```

Navigate to `http://localhost/enrollment` and verify:
- Page header shows period and deadline
- Toolbar renders with search + segmented control + chips
- Subject list shows 11 subjects (PRG-202 hidden by default via hideCompleted)
- ALG-302, CAL-301, ING-303 show as pre-selected
- Expanding ALG-302 shows 3 section cards
- Right panel shows UC count (15), hours, mini grid with 3 colored blocks
- Hovering a non-selected section previews it ghosted in the mini grid
- On narrow viewport (< 820 px), layout stacks vertically

---

## Self-review

**Spec coverage check:**

| Requirement | Task |
|---|---|
| Split layout desktop | Task 10 (`enr-layout--split`) |
| Cards/stacked mobile | Task 10 (`enr-layout--mobile`) |
| Auto-switch at 820px | Task 10 (`useMediaQuery`) |
| Accordion with sections | Task 6 |
| Section cards: prof, room, slots, capacity | Task 5 |
| Conflict detection + block | Task 3 (`slotsOverlap`), Task 5 (disabled state) |
| UC tracking bar min/max | Task 8 |
| Ghost preview on hover | Task 4 (`ghostCandidate`), Task 6 (mouseenter/leave) |
| No-schedule sections selectable | Task 5 (`noSchedule` badge), Task 8 (pending list) |
| Filters: type, trim, prereqs, completed, search | Task 3 (`useEnrollmentFilters`), Task 7 |
| Confirm CTA disabled until min UC | Task 8 |
| Mock data 12 subjects | Task 2 |
| Laravel route + Inertia render | Task 9 |
| Wayfinder route file | Task 9 |
| Nav item in sidebar | Task 11 |
| Pest route tests | Task 12 |

All spec requirements covered. ✓
