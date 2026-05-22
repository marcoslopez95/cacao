# Horarios Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the basic schedule grid with a rich calendar view matching `CACAO Horarios.html`: proper overlap layout, career color coding, stats row, legend, conflict detection, day/list views, and mobile navigation.

**Architecture:** Backend adds career data to `ScheduleResource` (via `subject.pensum.career`). A new `useScheduleLayout` composable handles all pure logic (overlap packing, conflict detection, career colors, time math). The page is decomposed into small focused components per the CACAO frontend architecture. No new routes or backend actions are needed — the existing `scheduling.schedules.index` endpoint covers everything.

**Tech Stack:** Laravel 13, PHP 8.3, Vue 3, Inertia.js v3, Tailwind v4, TypeScript, Pest v4

---

## File Map

**Backend (modify):**
- `app/Http/Controllers/Scheduling/ScheduleController.php` — add `subject.pensum.career` to eager loading
- `app/Http/Resources/Scheduling/ScheduleResource.php` — add `career` field

**Backend (create):**
- `tests/Feature/Scheduling/ScheduleResourceCareerTest.php` — verify resource includes career

**Frontend (create):**
- `resources/js/composables/scheduling/useScheduleLayout.ts` — pure layout/conflict/color logic
- `resources/js/components/scheduling/ScheduleEventCard.vue` — single event card
- `resources/js/components/scheduling/ScheduleOverflowTile.vue` — "+N more" overflow tile
- `resources/js/components/scheduling/ScheduleClusterPopover.vue` — event detail / overflow overlay
- `resources/js/components/scheduling/ScheduleStats.vue` — 4 KPI cards row
- `resources/js/components/scheduling/ScheduleLegend.vue` — career color legend/filter
- `resources/js/components/scheduling/ScheduleConflictsBanner.vue` — inline conflicts list
- `resources/js/components/scheduling/ScheduleListView.vue` — list view grouped by day
- `resources/js/components/scheduling/ScheduleToolbar.vue` — period selector, date nav, view switcher, search, create button

**Frontend (modify):**
- `resources/js/types/scheduling.ts` — add `career` to `Schedule` type
- `resources/js/components/scheduling/WeeklyGrid.vue` — full rewrite
- `resources/js/pages/scheduling/Schedules/Index.vue` — full rewrite

---

## Task 1: Backend — Add career to ScheduleResource

**Files:**
- Modify: `app/Http/Controllers/Scheduling/ScheduleController.php`
- Modify: `app/Http/Resources/Scheduling/ScheduleResource.php`

- [ ] **Step 1: Update eager loading in ScheduleController**

In `ScheduleController::index()` change the `with()` call from:
```php
$schedules = Schedule::with(['section', 'professor.user', 'classroom', 'subject'])
```
to:
```php
$schedules = Schedule::with(['section', 'professor.user', 'classroom', 'subject.pensum.career'])
```

- [ ] **Step 2: Add career field to ScheduleResource**

In `ScheduleResource::toArray()`, after `'subject' => [...]`, add:
```php
'career' => $this->subject->pensum?->career
    ? [
        'id'   => $this->subject->pensum->career->id,
        'name' => $this->subject->pensum->career->name,
    ]
    : null,
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Expected: no errors, files formatted.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Scheduling/ScheduleController.php app/Http/Resources/Scheduling/ScheduleResource.php
git commit -m "feat(scheduling): add career info to ScheduleResource"
```

---

## Task 2: Write and run the backend test

**Files:**
- Create: `tests/Feature/Scheduling/ScheduleResourceCareerTest.php`

- [ ] **Step 1: Create test file**

```bash
vendor/bin/sail artisan make:test --pest Feature/Scheduling/ScheduleResourceCareerTest
```

- [ ] **Step 2: Write the test**

Replace the generated file content with:
```php
<?php

use App\Http\Resources\Scheduling\ScheduleResource;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;

it('includes career info when subject has a pensum with career', function (): void {
    $career  = Career::factory()->create(['name' => 'Ingeniería de Sistemas']);
    $pensum  = Pensum::factory()->for($career)->create();
    $subject = Subject::factory()->for($pensum)->create();
    $schedule = Schedule::factory()
        ->for(Section::factory())
        ->for(Professor::factory())
        ->for(Classroom::factory())
        ->for($subject)
        ->create();

    $resource = (new ScheduleResource(
        $schedule->load(['section', 'professor.user', 'classroom', 'subject.pensum.career'])
    ))->toArray(request());

    expect($resource['career'])->toBe(['id' => $career->id, 'name' => 'Ingeniería de Sistemas']);
});

it('returns null career when subject has no pensum', function (): void {
    $subject  = Subject::factory()->create(['pensum_id' => null]);
    $schedule = Schedule::factory()
        ->for(Section::factory())
        ->for(Professor::factory())
        ->for(Classroom::factory())
        ->for($subject)
        ->create();

    $resource = (new ScheduleResource(
        $schedule->load(['section', 'professor.user', 'classroom', 'subject.pensum.career'])
    ))->toArray(request());

    expect($resource['career'])->toBeNull();
});
```

- [ ] **Step 3: Run test**

```bash
vendor/bin/sail artisan test --compact --filter=ScheduleResourceCareerTest
```

Expected: 2 tests passing.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Scheduling/ScheduleResourceCareerTest.php
git commit -m "test(scheduling): verify ScheduleResource includes career data"
```

---

## Task 3: Create useScheduleLayout composable

**Files:**
- Create: `resources/js/composables/scheduling/useScheduleLayout.ts`

- [ ] **Step 1: Create the file**

```typescript
// resources/js/composables/scheduling/useScheduleLayout.ts
import type { Schedule } from '@/types/scheduling'

export const HOUR_START = 7
export const HOUR_END = 20
export const PX_PER_HOUR = 56
export const GRID_HEIGHT = (HOUR_END - HOUR_START) * PX_PER_HOUR

export const DAY_KEYS = [
    'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday',
] as const
export type DayKey = (typeof DAY_KEYS)[number]

export const DAY_ABBRS = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB']
export const DAY_LABELS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']

export function toMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number)
    return h * 60 + m
}

export function minutesToPx(minutes: number): number {
    return ((minutes - HOUR_START * 60) / 60) * PX_PER_HOUR
}

export function scheduleTop(s: Schedule): number {
    return minutesToPx(toMinutes(s.startTime))
}

export function scheduleHeight(s: Schedule): number {
    return ((toMinutes(s.endTime) - toMinutes(s.startTime)) / 60) * PX_PER_HOUR
}

export function formatDuration(s: Schedule): string {
    const m = toMinutes(s.endTime) - toMinutes(s.startTime)
    const h = Math.floor(m / 60)
    const mm = m % 60
    return mm ? `${h}h ${mm}m` : `${h}h`
}

export function formatMinutes(m: number): string {
    return `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`
}

// Career color palette — assigned round-robin by career ID
const CAREER_COLORS = [
    '#C8521A', '#7C5A3A', '#2E7D5C', '#5B5A8A',
    '#A36B2D', '#3D6B8A', '#7A3578', '#2D7A6B',
]
const colorCache = new Map<number, string>()

export function careerColor(careerId: number): string {
    if (!colorCache.has(careerId)) {
        colorCache.set(careerId, CAREER_COLORS[colorCache.size % CAREER_COLORS.length])
    }
    return colorCache.get(careerId)!
}

export function scheduleColor(s: Schedule): string {
    return s.career ? careerColor(s.career.id) : '#888780'
}

// Lane-packing layout (mirrors Google Calendar overlap behaviour)
const MAX_LANES = 3

export type LayoutEvent = {
    kind: 'event'
    schedule: Schedule
    lane: number
    lanes: number
}

export type LayoutOverflow = {
    kind: 'overflow'
    schedules: Schedule[]
    lane: number
    lanes: number
    startMin: number
    endMin: number
}

export type LayoutItem = LayoutEvent | LayoutOverflow

export function layoutDaySchedules(daySchedules: Schedule[]): LayoutItem[] {
    const sorted = [...daySchedules].sort(
        (a, b) =>
            toMinutes(a.startTime) - toMinutes(b.startTime) ||
            toMinutes(b.endTime) - toMinutes(a.endTime),
    )

    const out: LayoutItem[] = []
    let cluster: Schedule[] = []
    let clusterEnd = -Infinity

    const flush = (): void => {
        if (!cluster.length) return
        const laneEnds: number[] = []
        const laneMap = new Map<Schedule, number>()
        cluster.forEach((s) => {
            let li = laneEnds.findIndex((end) => end <= toMinutes(s.startTime))
            if (li === -1) {
                li = laneEnds.length
                laneEnds.push(0)
            }
            laneEnds[li] = toMinutes(s.endTime)
            laneMap.set(s, li)
        })

        const total = laneEnds.length
        if (total <= MAX_LANES) {
            cluster.forEach((s) =>
                out.push({ kind: 'event', schedule: s, lane: laneMap.get(s)!, lanes: total }),
            )
        } else {
            const visible = cluster.filter((s) => laneMap.get(s)! < MAX_LANES - 1)
            const hidden = cluster.filter((s) => laneMap.get(s)! >= MAX_LANES - 1)
            visible.forEach((s) =>
                out.push({ kind: 'event', schedule: s, lane: laneMap.get(s)!, lanes: MAX_LANES }),
            )
            const startMin = Math.min(...hidden.map((s) => toMinutes(s.startTime)))
            const endMin = Math.max(...hidden.map((s) => toMinutes(s.endTime)))
            out.push({
                kind: 'overflow',
                schedules: [...hidden].sort(
                    (a, b) => toMinutes(a.startTime) - toMinutes(b.startTime),
                ),
                lane: MAX_LANES - 1,
                lanes: MAX_LANES,
                startMin,
                endMin,
            })
        }
        cluster = []
    }

    sorted.forEach((s) => {
        if (toMinutes(s.startTime) >= clusterEnd) flush()
        cluster.push(s)
        clusterEnd = Math.max(clusterEnd, toMinutes(s.endTime))
    })
    flush()
    return out
}

// Conflict detection — professor double-booked or room double-booked
export type ConflictType = 'professor' | 'room'

export interface Conflict {
    scheduleId: number
    type: ConflictType
    reason: string
}

export function detectConflicts(schedules: Schedule[]): Map<number, Conflict> {
    const map = new Map<number, Conflict>()
    for (let i = 0; i < schedules.length; i++) {
        for (let j = i + 1; j < schedules.length; j++) {
            const a = schedules[i]
            const b = schedules[j]
            if (a.dayOfWeek !== b.dayOfWeek) continue
            const overlap =
                toMinutes(a.startTime) < toMinutes(b.endTime) &&
                toMinutes(b.startTime) < toMinutes(a.endTime)
            if (!overlap) continue
            if (a.professor.id === b.professor.id) {
                const reason = `${a.professor.user.name} tiene otra clase a la misma hora`
                map.set(a.id, { scheduleId: a.id, type: 'professor', reason })
                map.set(b.id, { scheduleId: b.id, type: 'professor', reason })
            }
            if (a.classroom.id === b.classroom.id) {
                const reason = `El aula ${a.classroom.identifier} ya está ocupada`
                map.set(a.id, { scheduleId: a.id, type: 'room', reason })
                map.set(b.id, { scheduleId: b.id, type: 'room', reason })
            }
        }
    }
    return map
}

// Current day of week key (null on Sundays since we don't show Sunday)
export function todayKey(): DayKey | null {
    const d = new Date().getDay() // 0=Sun, 1=Mon … 6=Sat
    return d >= 1 && d <= 6 ? DAY_KEYS[d - 1] : null
}

// Current time in minutes since midnight
export function nowMinutes(): number {
    const n = new Date()
    return n.getHours() * 60 + n.getMinutes()
}

// Current week dates (Mon–Sat) as day-of-month strings
export function currentWeekDates(): string[] {
    const now = new Date()
    const dow = now.getDay()
    const diff = dow === 0 ? -6 : 1 - dow
    const monday = new Date(now)
    monday.setDate(now.getDate() + diff)
    return Array.from({ length: 6 }, (_, i) => {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)
        return String(d.getDate())
    })
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/scheduling/useScheduleLayout.ts
git commit -m "feat(scheduling): add useScheduleLayout composable (layout, conflicts, colors)"
```

---

## Task 4: Create ScheduleEventCard component

**Files:**
- Create: `resources/js/components/scheduling/ScheduleEventCard.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup lang="ts">
import type { Schedule } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { scheduleColor, scheduleHeight, scheduleTop, formatDuration, PX_PER_HOUR } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedule: Schedule
    lane?: number
    lanes?: number
    conflict?: Conflict
    canUpdate?: boolean
    canDelete?: boolean
}>()

const emit = defineEmits<{
    open: []
    edit: []
    delete: []
}>()

const lane   = props.lane  ?? 0
const lanes  = props.lanes ?? 1
const top    = scheduleTop(props.schedule)
const height = Math.max(scheduleHeight(props.schedule) - 2, 20)
const widthPct = 100 / lanes
const leftPct  = lane * widthPct
const color    = scheduleColor(props.schedule)
const compact  = height < PX_PER_HOUR
const overlapped = lanes > 1

const initials = props.schedule.professor.user.name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
</script>

<template>
    <div
        role="button"
        tabindex="0"
        class="sch-evt"
        :class="{ compact, overlapped, conflict: !!conflict }"
        :style="{
            top:    top    + 'px',
            height: height + 'px',
            left:   `calc(${leftPct}% + 4px)`,
            width:  `calc(${widthPct}% - 6px)`,
            zIndex: 2 + lane,
            '--evt-color':  color,
            '--evt-bg':     `color-mix(in srgb, ${color} 8%, var(--bg-surface))`,
            '--evt-border': `color-mix(in srgb, ${color} 22%, var(--border))`,
        }"
        @click="emit('open')"
        @keydown.enter.prevent="emit('open')"
        @keydown.space.prevent="emit('open')"
    >
        <div class="evt-time">
            {{ schedule.startTime }}–{{ schedule.endTime }}
            <span v-if="!compact" class="dur">{{ formatDuration(schedule) }}</span>
        </div>
        <div class="evt-title">{{ schedule.subject.name }}</div>
        <template v-if="!compact">
            <div class="evt-meta">
                <span class="sec">{{ schedule.section.code }}</span>
                <span class="room">{{ schedule.classroom.identifier }}</span>
            </div>
            <div class="evt-prof">
                <span class="dot">{{ initials }}</span>
                {{ schedule.professor.user.name }}
            </div>
        </template>

        <div v-if="canUpdate || canDelete" class="evt-actions">
            <button v-if="canUpdate" title="Editar" @click.stop="emit('edit')">✎</button>
            <button v-if="canDelete" class="danger" title="Eliminar" @click.stop="emit('delete')">✕</button>
        </div>
    </div>
</template>

<style scoped>
.sch-evt {
    position: absolute;
    border-radius: var(--r-sm);
    padding: 8px 10px 8px 14px;
    cursor: pointer;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 3px;
    border: 1px solid var(--evt-border, var(--border));
    background: var(--evt-bg, var(--bg-surface-2));
    color: var(--text-primary);
    font-family: inherit;
    transition: box-shadow 0.15s, border-color 0.15s;
    container-type: inline-size;
    text-align: left;
}
.sch-evt::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--evt-color, var(--accent));
}
.sch-evt:hover {
    box-shadow: var(--shadow-md);
    z-index: 50 !important;
    border-color: var(--evt-color, var(--accent));
}
.sch-evt.overlapped { padding: 6px 8px 6px 12px; }
.sch-evt.overlapped::before { width: 3px; }
.sch-evt.compact { padding: 4px 8px 4px 12px; gap: 1px; }
.sch-evt.conflict { border-color: var(--danger) !important; background: color-mix(in srgb, var(--danger) 6%, var(--bg-surface-2)); }
.sch-evt.conflict::after { content: '⚠'; position: absolute; top: 5px; right: 7px; font-size: 11px; color: var(--danger); }

.evt-time {
    font-size: 10.5px;
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    flex-shrink: 0;
}
.dur {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    padding: 0 4px;
    border-radius: 3px;
    font-size: 9.5px;
}
.evt-title {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.25;
    color: var(--text-primary);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex-shrink: 0;
    word-break: break-word;
}
.evt-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--text-secondary);
    flex-wrap: wrap;
}
.sec {
    background: var(--bg-surface);
    padding: 1px 6px;
    border-radius: 3px;
    border: 1px solid var(--border);
    font-size: 10.5px;
    font-weight: 600;
}
.evt-prof {
    font-size: 11px;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: auto;
    overflow: hidden;
    white-space: nowrap;
}
.dot {
    width: 14px; height: 14px;
    border-radius: 50%;
    background: var(--evt-color);
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 8.5px;
    font-weight: 700;
    flex-shrink: 0;
}

.evt-actions {
    position: absolute;
    top: 4px; right: 4px;
    display: flex;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.12s;
}
.sch-evt:hover .evt-actions { opacity: 1; }
.evt-actions button {
    width: 22px; height: 22px;
    border-radius: 4px;
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-secondary);
    cursor: pointer;
    display: grid;
    place-items: center;
    font-size: 11px;
}
.evt-actions button:hover { background: var(--bg-surface-2); color: var(--text-primary); }
.evt-actions button.danger:hover {
    background: color-mix(in srgb, var(--danger) 12%, transparent);
    color: var(--danger);
    border-color: var(--danger);
}

@container (max-width: 110px) { .evt-meta { display: none; } }
@container (max-width: 80px)  { .evt-prof { display: none; } .sch-evt { padding: 6px 6px 6px 10px; } }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/ScheduleEventCard.vue
git commit -m "feat(scheduling): add ScheduleEventCard component"
```

---

## Task 5: Create ScheduleOverflowTile component

**Files:**
- Create: `resources/js/components/scheduling/ScheduleOverflowTile.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup lang="ts">
import type { Schedule } from '@/types/scheduling'
import {
    scheduleColor,
    minutesToPx,
    PX_PER_HOUR,
    formatMinutes,
} from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: Schedule[]
    lane: number
    lanes: number
    startMin: number
    endMin: number
    hasConflict?: boolean
}>()

const emit = defineEmits<{ open: [] }>()

const top      = minutesToPx(props.startMin)
const height   = Math.max(((props.endMin - props.startMin) / 60) * PX_PER_HOUR - 2, 20)
const widthPct = 100 / props.lanes
const leftPct  = props.lane * widthPct
const dotColors = [...new Set(props.schedules.map(scheduleColor))].slice(0, 5)
</script>

<template>
    <button
        class="sch-overflow"
        :class="{ 'has-conflict': hasConflict }"
        :style="{
            top:    top    + 'px',
            height: height + 'px',
            left:   `calc(${leftPct}% + 4px)`,
            width:  `calc(${widthPct}% - 6px)`,
        }"
        @click="emit('open')"
    >
        <span class="ov-count">+{{ schedules.length }}</span>
        <span class="ov-label">clases<br />simultáneas</span>
        <span class="ov-time">{{ formatMinutes(startMin) }}–{{ formatMinutes(endMin) }}</span>
        <div class="ov-dots">
            <span v-for="(c, i) in dotColors" :key="i" :style="{ background: c }" />
        </div>
        <span v-if="hasConflict" class="ov-warn">⚠</span>
    </button>
</template>

<style scoped>
.sch-overflow {
    position: absolute;
    border-radius: var(--r-sm);
    border: 1px dashed var(--border-strong);
    background: repeating-linear-gradient(135deg, var(--bg-surface-2) 0 6px, var(--bg-surface) 6px 12px);
    color: var(--text-primary);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 3px;
    padding: 8px 10px;
    font-family: inherit;
    text-align: left;
    z-index: 3;
    overflow: hidden;
    transition: border-color 0.12s, background 0.12s;
}
.sch-overflow:hover {
    border-color: var(--accent);
    border-style: solid;
    background: var(--bg-surface);
    z-index: 50;
    box-shadow: var(--shadow-md);
}
.ov-count { font-size: 18px; font-weight: 700; line-height: 1; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.ov-label { font-size: 10px; line-height: 1.15; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
.ov-time  { font-size: 10px; color: var(--text-muted); font-variant-numeric: tabular-nums; margin-top: 2px; }
.ov-dots  { display: flex; gap: 3px; margin-top: auto; padding-top: 6px; }
.ov-dots span { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.has-conflict { border-color: color-mix(in srgb, var(--danger) 50%, var(--border-strong)); }
.ov-warn { position: absolute; top: 6px; right: 6px; color: var(--danger); font-size: 12px; }

@container (max-width: 90px) { .ov-label, .ov-time { display: none; } .sch-overflow { padding: 6px 8px; } }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/ScheduleOverflowTile.vue
git commit -m "feat(scheduling): add ScheduleOverflowTile component"
```

---

## Task 6: Rewrite WeeklyGrid.vue

**Files:**
- Modify: `resources/js/components/scheduling/WeeklyGrid.vue`

- [ ] **Step 1: Replace the entire file content**

```vue
<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import ScheduleEventCard from '@/components/scheduling/ScheduleEventCard.vue'
import ScheduleOverflowTile from '@/components/scheduling/ScheduleOverflowTile.vue'
import {
    DAY_KEYS, DAY_ABBRS, DAY_LABELS,
    HOUR_START, HOUR_END, PX_PER_HOUR, GRID_HEIGHT,
    layoutDaySchedules, scheduleTop, minutesToPx,
    todayKey, nowMinutes, currentWeekDates, formatMinutes,
} from '@/composables/scheduling/useScheduleLayout'
import type { Schedule, ScheduleCollection } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: ScheduleCollection
    conflicts: Map<number, Conflict>
    canUpdate?: boolean
    canDelete?: boolean
    mobileDay?: number
    dayMode?: boolean
}>()

const emit = defineEmits<{
    create:        [{ dayOfWeek: string; startTime: string }]
    openEvent:     [schedule: Schedule]
    openCluster:   [{ dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }]
    editSchedule:  [schedule: Schedule]
    deleteSchedule:[schedule: Schedule]
    'update:mobileDay': [value: number]
}>()

const today    = todayKey()
const weekDates = currentWeekDates()
const hours    = Array.from({ length: HOUR_END - HOUR_START + 1 }, (_, i) => HOUR_START + i)
const nowMin   = ref(nowMinutes())

let ticker: ReturnType<typeof setInterval>
onMounted(()    => { ticker = setInterval(() => { nowMin.value = nowMinutes() }, 60_000) })
onBeforeUnmount(() => clearInterval(ticker))

const visibleDays = computed(() =>
    props.dayMode ? [props.mobileDay ?? 0] : [0, 1, 2, 3, 4, 5],
)
const nowTop     = computed(() => minutesToPx(nowMin.value))
const nowVisible = computed(() => nowMin.value >= HOUR_START * 60 && nowMin.value <= HOUR_END * 60)

function schedulesForDay(i: number): Schedule[] {
    return props.schedules.filter((s) => s.dayOfWeek === DAY_KEYS[i])
}

function handleDayClick(dayIndex: number, e: MouseEvent): void {
    if (!props.canUpdate) return
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    const clickY = e.clientY - rect.top
    const totalMin = Math.floor((clickY / PX_PER_HOUR) * 60) + HOUR_START * 60
    const h  = Math.min(Math.max(Math.floor(totalMin / 60), HOUR_START), HOUR_END - 1)
    const m  = Math.floor((totalMin % 60) / 15) * 15
    emit('create', {
        dayOfWeek: DAY_KEYS[dayIndex],
        startTime: `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`,
    })
}
</script>

<template>
    <div class="sch-grid-wrap">

        <!-- Mobile day navigation (hidden on desktop) -->
        <div class="sch-mobile-nav">
            <button
                :disabled="(mobileDay ?? 0) === 0"
                aria-label="Día anterior"
                @click="emit('update:mobileDay', Math.max(0, (mobileDay ?? 0) - 1))"
            >‹</button>
            <div class="sch-mobile-dots">
                <button
                    v-for="(abbr, i) in DAY_ABBRS"
                    :key="i"
                    :class="{ active: i === (mobileDay ?? 0), 'has-events': schedulesForDay(i).length > 0 }"
                    @click="emit('update:mobileDay', i)"
                >{{ abbr }}</button>
            </div>
            <button
                :disabled="(mobileDay ?? 0) === 5"
                aria-label="Día siguiente"
                @click="emit('update:mobileDay', Math.min(5, (mobileDay ?? 0) + 1))"
            >›</button>
        </div>

        <!-- Calendar grid -->
        <div
            class="sch-grid"
            :style="dayMode ? { gridTemplateColumns: '64px 1fr' } : undefined"
        >
            <!-- Sticky header: gutter corner -->
            <div class="sch-gutter-head" />

            <!-- Sticky header: day columns -->
            <div
                v-for="i in visibleDays"
                :key="'h' + i"
                class="sch-day-head"
                :class="{
                    today: DAY_KEYS[i] === today,
                    'active-mobile': i === (mobileDay ?? 0),
                }"
            >
                <span class="dow">{{ DAY_ABBRS[i] }}</span>
                <span class="dnum">{{ weekDates[i] }}</span>
                <span class="meta">
                    {{ schedulesForDay(i).length }}
                    clase{{ schedulesForDay(i).length !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Time gutter -->
            <div class="sch-gutter" :style="{ height: GRID_HEIGHT + 'px' }">
                <div
                    v-for="(h, hi) in hours"
                    :key="h"
                    class="sch-gutter-tick"
                    :style="{ top: hi * PX_PER_HOUR - 7 + 'px' }"
                >
                    {{ String(h).padStart(2, '0') }}:00
                </div>
            </div>

            <!-- Day columns -->
            <div
                v-for="i in visibleDays"
                :key="'c' + i"
                class="sch-day-col"
                :class="{
                    today:   DAY_KEYS[i] === today,
                    weekend: i === 5,
                    'active-mobile': i === (mobileDay ?? 0),
                }"
                :style="{ height: GRID_HEIGHT + 'px', cursor: canUpdate ? 'pointer' : 'default' }"
                @click.self="(e) => handleDayClick(i, e)"
            >
                <!-- Hour grid lines -->
                <template v-for="(_, hi) in hours" :key="'l' + hi">
                    <div class="sch-hline" :style="{ top: hi * PX_PER_HOUR + 'px' }" />
                    <div
                        v-if="hi < hours.length - 1"
                        class="sch-hline half"
                        :style="{ top: hi * PX_PER_HOUR + PX_PER_HOUR / 2 + 'px' }"
                    />
                </template>

                <!-- Now indicator -->
                <div
                    v-if="DAY_KEYS[i] === today && nowVisible"
                    class="sch-now"
                    :style="{ top: nowTop + 'px' }"
                >
                    <span class="sch-now-label">{{ formatMinutes(nowMin) }}</span>
                    <div class="sch-now-line" />
                </div>

                <!-- Events and overflow tiles -->
                <template
                    v-for="item in layoutDaySchedules(schedulesForDay(i))"
                    :key="item.kind === 'event' ? item.schedule.id : `ov-${i}-${item.startMin}`"
                >
                    <ScheduleEventCard
                        v-if="item.kind === 'event'"
                        :schedule="item.schedule"
                        :lane="item.lane"
                        :lanes="item.lanes"
                        :conflict="conflicts.get(item.schedule.id)"
                        :can-update="canUpdate"
                        :can-delete="canDelete"
                        @open="emit('openEvent', item.schedule)"
                        @edit="emit('editSchedule', item.schedule)"
                        @delete="emit('deleteSchedule', item.schedule)"
                    />
                    <ScheduleOverflowTile
                        v-else
                        :schedules="item.schedules"
                        :lane="item.lane"
                        :lanes="item.lanes"
                        :start-min="item.startMin"
                        :end-min="item.endMin"
                        :has-conflict="item.schedules.some((s) => conflicts.has(s.id))"
                        @open="emit('openCluster', { dayIndex: i, startMin: item.startMin, endMin: item.endMin, schedules: item.schedules })"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sch-grid-wrap {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    overflow: hidden;
}

/* Mobile nav (hidden by default, shown on ≤820px) */
.sch-mobile-nav { display: none; }

@media (max-width: 820px) {
    .sch-mobile-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--bg-surface-2);
        border-bottom: 1px solid var(--border);
        gap: 8px;
    }
    .sch-mobile-nav > button {
        width: 32px; height: 32px;
        background: var(--bg-surface);
        border: 1px solid var(--border-strong);
        border-radius: var(--r-sm);
        color: var(--text-primary);
        cursor: pointer;
        display: grid; place-items: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .sch-mobile-nav > button:disabled { opacity: 0.4; cursor: default; }
}

.sch-mobile-dots {
    display: flex;
    gap: 4px;
    flex: 1;
    justify-content: center;
}
.sch-mobile-dots button {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    position: relative;
}
.sch-mobile-dots button.active { background: var(--accent); color: #fff; }
.sch-mobile-dots button.has-events::after {
    content: '';
    display: block;
    width: 4px; height: 4px;
    background: var(--text-muted);
    border-radius: 50%;
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
}
.sch-mobile-dots button.active.has-events::after { background: rgba(255,255,255,0.7); }

/* Grid layout */
.sch-grid {
    display: grid;
    grid-template-columns: 64px repeat(6, 1fr);
    overflow-x: auto;
    min-width: 640px;
}

@media (max-width: 820px) {
    .sch-grid { grid-template-columns: 48px 1fr; min-width: 0; }
    .sch-day-head:not(.active-mobile) { display: none; }
    .sch-day-col:not(.active-mobile)  { display: none; }
}

/* Sticky header */
.sch-gutter-head {
    position: sticky; top: 0; z-index: 5;
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    border-right:  1px solid var(--border);
}
.sch-day-head {
    position: sticky; top: 0; z-index: 5;
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    border-left:   1px solid var(--border);
    padding: 12px 12px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.sch-day-head .dow  { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); font-weight: 600; }
.sch-day-head .dnum { font-size: 18px; font-weight: 600; color: var(--text-primary); font-variant-numeric: tabular-nums; }
.sch-day-head .meta { font-size: 11px; color: var(--text-muted); }
.sch-day-head.today .dnum { color: var(--accent); }

/* Gutter */
.sch-gutter {
    border-right: 1px solid var(--border);
    position: relative;
    background: var(--bg-surface);
}
.sch-gutter-tick {
    position: absolute;
    left: 0; right: 0;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 2px 8px 0 0;
    font-size: 11px;
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
    user-select: none;
}

/* Day columns */
.sch-day-col {
    position: relative;
    border-left: 1px solid var(--border);
    background: var(--bg-surface);
    container-type: inline-size;
}
.sch-day-col.today   { background: color-mix(in srgb, var(--accent) 4%, var(--bg-surface)); }
.sch-day-col.weekend { background: var(--bg-surface-2); }

/* Grid lines */
.sch-hline      { position: absolute; left: 0; right: 0; border-top: 1px solid var(--border); pointer-events: none; }
.sch-hline.half { border-top-style: dashed; border-top-color: color-mix(in srgb, var(--border) 60%, transparent); }

/* Now indicator */
.sch-now { position: absolute; left: 0; right: 0; z-index: 4; pointer-events: none; }
.sch-now::before {
    content: '';
    position: absolute;
    left: -4px; top: -4px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent);
}
.sch-now-line { height: 2px; background: var(--accent); border-radius: 1px; }
.sch-now-label {
    position: absolute;
    left: -56px; top: -10px;
    width: 48px;
    text-align: right;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--accent);
    font-variant-numeric: tabular-nums;
    background: var(--bg-surface);
    padding: 2px 4px;
    border-radius: 3px;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/WeeklyGrid.vue
git commit -m "feat(scheduling): rewrite WeeklyGrid with overlap layout and now-line"
```

---

## Task 7: Create ScheduleListView component

**Files:**
- Create: `resources/js/components/scheduling/ScheduleListView.vue`

- [ ] **Step 1: Create the file**

```vue
<script setup lang="ts">
import type { Schedule, ScheduleCollection } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { DAY_KEYS, DAY_LABELS, scheduleColor, formatDuration, todayKey } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: ScheduleCollection
    conflicts: Map<number, Conflict>
    canUpdate?: boolean
    canDelete?: boolean
}>()

const emit = defineEmits<{
    openEvent:     [schedule: Schedule]
    editSchedule:  [schedule: Schedule]
    deleteSchedule:[schedule: Schedule]
}>()

const today = todayKey()

function daySchedules(i: number): Schedule[] {
    return [...props.schedules]
        .filter((s) => s.dayOfWeek === DAY_KEYS[i])
        .sort((a, b) => a.startTime.localeCompare(b.startTime))
}

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
}
</script>

<template>
    <div class="sch-list">
        <div v-for="(_, i) in DAY_KEYS" :key="i" class="list-day">
            <div
                class="list-day-head"
                :class="{ today: DAY_KEYS[i] === today }"
            >
                {{ DAY_LABELS[i] }}
                <span class="count">{{ daySchedules(i).length }}</span>
            </div>

            <div v-if="daySchedules(i).length === 0" class="list-empty">Sin clases</div>

            <div
                v-for="s in daySchedules(i)"
                :key="s.id"
                class="list-row"
                :class="{ conflict: conflicts.has(s.id) }"
                :style="{ '--evt-color': scheduleColor(s) }"
                @click="emit('openEvent', s)"
            >
                <div class="list-time">
                    {{ s.startTime }}–{{ s.endTime }}
                    <span class="dur">{{ formatDuration(s) }}</span>
                </div>
                <div class="list-title">
                    <strong>{{ s.subject.name }}</strong>
                    <span class="carr" :style="{ color: scheduleColor(s) }">
                        {{ s.career?.name ?? '—' }}
                    </span>
                    <div class="list-meta-mobile">
                        {{ s.section.code }} · {{ s.classroom.identifier }} · {{ s.professor.user.name }}
                    </div>
                </div>
                <div class="list-sec">{{ s.section.code }}</div>
                <div class="list-room">{{ s.classroom.identifier }}</div>
                <div class="list-prof">
                    <span class="dot" :style="{ background: scheduleColor(s) }">{{ initials(s.professor.user.name) }}</span>
                    {{ s.professor.user.name }}
                </div>
                <div class="list-actions">
                    <button v-if="canUpdate" title="Editar" @click.stop="emit('editSchedule', s)">✎</button>
                    <button v-if="canDelete" class="danger" title="Eliminar" @click.stop="emit('deleteSchedule', s)">✕</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sch-list {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    overflow: hidden;
}
.list-day { border-bottom: 1px solid var(--border); }
.list-day:last-child { border-bottom: 0; }

.list-day-head {
    padding: 10px 16px;
    background: var(--bg-surface-2);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border);
}
.list-day-head.today { color: var(--accent); }
.count {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    padding: 1px 8px;
    border-radius: 999px;
    font-size: 11px;
    color: var(--text-muted);
}

.list-empty { padding: 24px 16px; text-align: center; color: var(--text-muted); font-size: 13px; }

.list-row {
    display: grid;
    grid-template-columns: 110px 1fr 120px 160px 160px 80px;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    position: relative;
    cursor: pointer;
}
.list-row::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--evt-color);
}
.list-row:last-child { border-bottom: 0; }
.list-row:hover { background: var(--bg-surface-2); }
.list-row.conflict { background: color-mix(in srgb, var(--danger) 4%, transparent); }

.list-time {
    font-size: 12.5px;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}
.dur { display: block; font-size: 10.5px; font-weight: 400; color: var(--text-muted); margin-top: 2px; }

.list-title { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.list-title strong { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.list-title .carr  { font-size: 11.5px; font-weight: 500; }
.list-meta-mobile  { display: none; }

.list-sec, .list-room { font-size: 12.5px; color: var(--text-secondary); }
.list-prof { font-size: 12.5px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 8px; min-width: 0; overflow: hidden; }
.dot { width: 22px; height: 22px; border-radius: 50%; color: #fff; display: grid; place-items: center; font-size: 9.5px; font-weight: 700; flex-shrink: 0; }

.list-actions { display: flex; gap: 4px; justify-content: flex-end; }
.list-actions button {
    width: 28px; height: 28px;
    border-radius: var(--r-sm);
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-secondary);
    cursor: pointer;
    display: grid; place-items: center;
    font-size: 13px;
}
.list-actions button:hover { background: var(--bg-surface-2); color: var(--text-primary); }
.list-actions button.danger:hover { background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger); border-color: var(--danger); }

@media (max-width: 820px) {
    .list-row { grid-template-columns: 80px 1fr 32px; grid-template-areas: "time title actions" "time meta  actions"; gap: 4px 12px; padding: 10px 12px; }
    .list-time    { grid-area: time; }
    .list-title   { grid-area: title; }
    .list-sec, .list-room, .list-prof { display: none; }
    .list-actions { grid-area: actions; }
    .list-meta-mobile { display: flex; flex-wrap: wrap; gap: 8px; font-size: 11.5px; color: var(--text-muted); }
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/ScheduleListView.vue
git commit -m "feat(scheduling): add ScheduleListView component"
```

---

## Task 8: Create ScheduleClusterPopover component

**Files:**
- Create: `resources/js/components/scheduling/ScheduleClusterPopover.vue`

This component renders a full-screen scrim with a centered panel. It handles three modes: `event` (single event detail), `cluster` (overflow group), `conflicts` (all conflicts).

- [ ] **Step 1: Create the file**

```vue
<script setup lang="ts">
import type { Schedule } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { scheduleColor, formatDuration, formatMinutes, DAY_LABELS } from '@/composables/scheduling/useScheduleLayout'

export type PopoverData =
    | { kind: 'event';     schedule: Schedule; conflict?: Conflict }
    | { kind: 'cluster';   dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }
    | { kind: 'conflicts'; schedules: Schedule[] }

const props = defineProps<{
    data: PopoverData | null
    conflicts: Map<number, Conflict>
}>()

const emit = defineEmits<{
    close:         []
    editSchedule:  [schedule: Schedule]
    deleteSchedule:[schedule: Schedule]
}>()

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
}

function conflictExplain(c: Conflict | undefined): string {
    if (!c) return ''
    return c.type === 'professor'
        ? `${c.reason}`
        : c.reason
}
</script>

<template>
    <Teleport to="body">
        <div v-if="data" class="pop-scrim" @click.self="emit('close')">
            <div
                class="pop-panel"
                :class="{ 'conflicts-mode': data.kind === 'conflicts', 'event-mode': data.kind === 'event' }"
                role="dialog"
                aria-modal="true"
            >
                <!-- Header -->
                <div class="pop-head">
                    <div>
                        <div class="pop-title">
                            <template v-if="data.kind === 'cluster'">
                                {{ data.schedules.length }} clases simultáneas
                            </template>
                            <template v-else-if="data.kind === 'conflicts'">
                                {{ data.schedules.length }} conflicto(s) detectados
                            </template>
                            <template v-else>
                                {{ data.conflict ? `Conflicto · ${data.schedule.subject.name}` : data.schedule.subject.name }}
                            </template>
                        </div>
                        <div class="pop-sub">
                            <template v-if="data.kind === 'cluster'">
                                {{ DAY_LABELS[data.dayIndex] }} · {{ formatMinutes(data.startMin) }}–{{ formatMinutes(data.endMin) }}
                            </template>
                            <template v-else-if="data.kind === 'conflicts'">
                                Esta semana — clic en cada conflicto para ver el detalle
                            </template>
                            <template v-else>
                                {{ data.schedule.dayLabel }} · {{ data.schedule.startTime }}–{{ data.schedule.endTime }} · {{ formatDuration(data.schedule) }}
                            </template>
                        </div>
                    </div>
                    <button class="pop-close" aria-label="Cerrar" @click="emit('close')">✕</button>
                </div>

                <!-- Event-mode conflict banner -->
                <div
                    v-if="data.kind === 'event' && data.conflict"
                    class="pop-conflict-banner"
                >
                    <div class="ico">⚠</div>
                    <div class="body">
                        <div class="t">{{ data.conflict.type === 'professor' ? 'Profesor con doble asignación' : 'Aula ocupada simultáneamente' }}</div>
                        <div class="d">{{ conflictExplain(data.conflict) }}</div>
                    </div>
                    <button class="btn ghost small" @click="emit('editSchedule', data.schedule)">✎ Resolver</button>
                </div>

                <!-- List of rows -->
                <div class="pop-list">
                    <!-- Single event mode -->
                    <template v-if="data.kind === 'event'">
                        <div
                            class="pop-row focused"
                            :style="{ '--evt-color': scheduleColor(data.schedule) }"
                        >
                            <div class="pop-time">{{ data.schedule.startTime }}<br />{{ data.schedule.endTime }}</div>
                            <div class="pop-bar" />
                            <div class="pop-info">
                                <div class="pt">
                                    <strong>{{ data.schedule.subject.name }}</strong>
                                </div>
                                <div class="pm">
                                    <span class="sec">{{ data.schedule.section.code }}</span>
                                    <span class="carr" :style="{ color: scheduleColor(data.schedule) }">{{ data.schedule.career?.name ?? '—' }}</span>
                                    <span>{{ data.schedule.classroom.identifier }}</span>
                                    <span>
                                        <span class="prof-dot" :style="{ background: scheduleColor(data.schedule) }">
                                            {{ initials(data.schedule.professor.user.name) }}
                                        </span>
                                        {{ data.schedule.professor.user.name }}
                                    </span>
                                </div>
                            </div>
                            <div class="pop-row-actions">
                                <button title="Editar" @click="emit('editSchedule', data.schedule)">✎</button>
                                <button class="danger" title="Eliminar" @click="emit('deleteSchedule', data.schedule)">✕</button>
                            </div>
                        </div>
                    </template>

                    <!-- Cluster / conflicts mode -->
                    <template v-else>
                        <div
                            v-for="s in data.schedules"
                            :key="s.id"
                            class="pop-row"
                            :class="{ conflict: conflicts.has(s.id) }"
                            :style="{ '--evt-color': scheduleColor(s) }"
                        >
                            <div class="pop-time">{{ s.startTime }}<br />{{ s.endTime }}</div>
                            <div class="pop-bar" />
                            <div class="pop-info">
                                <div class="pt">
                                    <strong>{{ s.subject.name }}</strong>
                                    <span v-if="conflicts.has(s.id)" class="pop-flag">
                                        ⚠ {{ conflicts.get(s.id)!.type === 'professor' ? 'Profesor duplicado' : 'Aula duplicada' }}
                                    </span>
                                    <span v-if="data.kind === 'conflicts'" class="pop-day">{{ s.dayLabel }}</span>
                                </div>
                                <div class="pm">
                                    <span class="sec">{{ s.section.code }}</span>
                                    <span class="carr" :style="{ color: scheduleColor(s) }">{{ s.career?.name ?? '—' }}</span>
                                    <span>{{ s.classroom.identifier }}</span>
                                    <span>
                                        <span class="prof-dot" :style="{ background: scheduleColor(s) }">{{ initials(s.professor.user.name) }}</span>
                                        {{ s.professor.user.name }}
                                    </span>
                                </div>
                                <div v-if="conflicts.has(s.id) && data.kind === 'conflicts'" class="pop-explain">
                                    {{ conflictExplain(conflicts.get(s.id)) }}
                                </div>
                            </div>
                            <button class="pop-edit" title="Editar" @click="emit('editSchedule', s)">✎</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.pop-scrim {
    position: fixed; inset: 0;
    z-index: 200;
    background: color-mix(in srgb, var(--bg-base) 60%, transparent);
    backdrop-filter: blur(2px);
    display: grid;
    place-items: center;
    padding: 24px;
    animation: pop-fade 0.14s ease-out;
}
@keyframes pop-fade { from { opacity: 0; } to { opacity: 1; } }

.pop-panel {
    width: min(720px, 100%);
    max-height: 80vh;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-lg, 0 20px 60px rgba(0,0,0,0.35));
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: pop-in 0.16s ease-out;
}
@keyframes pop-in { from { opacity: 0; transform: scale(0.97) translateY(4px); } to { opacity: 1; transform: none; } }

.pop-head {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    background: var(--bg-surface-2);
}
.pop-title { font-size: 15px; font-weight: 600; color: var(--text-primary); }
.pop-sub   { font-size: 12px; color: var(--text-muted); margin-top: 2px; font-variant-numeric: tabular-nums; }
.pop-close {
    width: 28px; height: 28px;
    border-radius: var(--r-sm);
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-secondary);
    cursor: pointer; display: grid; place-items: center; flex-shrink: 0;
}
.pop-close:hover { background: var(--bg-sunken); color: var(--text-primary); }

.pop-conflict-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 16px;
    background: color-mix(in srgb, var(--danger) 8%, var(--bg-surface));
    border-bottom: 1px solid color-mix(in srgb, var(--danger) 25%, var(--border));
}
.pop-conflict-banner .ico {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--danger) 18%, transparent);
    color: var(--danger);
    display: grid; place-items: center;
    flex-shrink: 0;
}
.pop-conflict-banner .body { flex: 1; min-width: 0; }
.pop-conflict-banner .t { font-size: 13px; font-weight: 600; color: var(--danger); }
.pop-conflict-banner .d { font-size: 12px; color: var(--text-secondary); margin-top: 2px; line-height: 1.45; }

.pop-list { overflow-y: auto; padding: 4px 0; }

.pop-row {
    display: grid;
    grid-template-columns: 56px 4px 1fr 32px;
    gap: 12px;
    padding: 12px 16px;
    align-items: stretch;
    border-bottom: 1px solid var(--border);
}
.pop-row:last-child { border-bottom: 0; }
.pop-row:hover { background: var(--bg-surface-2); }
.pop-row.conflict { background: color-mix(in srgb, var(--danger) 4%, transparent); }
.pop-row.focused {
    background: color-mix(in srgb, var(--evt-color) 8%, var(--bg-surface));
    outline: 2px solid color-mix(in srgb, var(--evt-color) 50%, transparent);
    outline-offset: -2px;
}

.pop-time {
    font-size: 11.5px;
    font-variant-numeric: tabular-nums;
    color: var(--text-secondary);
    font-weight: 600;
    line-height: 1.4;
}
.pop-bar { background: var(--evt-color); border-radius: 2px; }
.pop-info { min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.pt { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pt strong { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.pm { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-muted); }
.pm .sec { background: var(--bg-surface-2); border: 1px solid var(--border); padding: 1px 6px; border-radius: 3px; font-weight: 600; font-size: 10.5px; }
.pm .carr { font-weight: 600; }
.prof-dot { display: inline-grid; place-items: center; width: 16px; height: 16px; border-radius: 50%; color: #fff; font-size: 8.5px; font-weight: 700; margin-right: 4px; vertical-align: -3px; }
.pop-flag { display: inline-flex; align-items: center; gap: 4px; padding: 1px 6px; border-radius: 3px; background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.pop-day  { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600; }
.pop-explain { margin-top: 4px; padding: 6px 8px; font-size: 11.5px; color: var(--danger); background: color-mix(in srgb, var(--danger) 6%, transparent); border-left: 2px solid var(--danger); border-radius: 2px; line-height: 1.4; }

.pop-edit, .pop-row-actions button {
    width: 28px; height: 28px;
    border-radius: var(--r-sm);
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-secondary);
    cursor: pointer;
    display: grid; place-items: center;
    align-self: center;
    font-size: 13px;
}
.pop-edit:hover, .pop-row-actions button:hover { background: var(--bg-surface-2); color: var(--text-primary); }
.pop-row-actions { display: flex; gap: 4px; align-self: center; }
.pop-row-actions button.danger:hover { background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger); border-color: var(--danger); }

.btn.ghost.small { padding: 4px 10px; font-size: 12px; height: 28px; border-radius: var(--r-sm); border: 1px solid var(--border); background: transparent; color: var(--text-secondary); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; white-space: nowrap; }
.btn.ghost.small:hover { background: var(--bg-surface-2); color: var(--text-primary); }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/ScheduleClusterPopover.vue
git commit -m "feat(scheduling): add ScheduleClusterPopover component"
```

---

## Task 9: Create ScheduleStats and ScheduleLegend components

**Files:**
- Create: `resources/js/components/scheduling/ScheduleStats.vue`
- Create: `resources/js/components/scheduling/ScheduleLegend.vue`

- [ ] **Step 1: Create ScheduleStats.vue**

```vue
<script setup lang="ts">
import type { ScheduleCollection } from '@/types/scheduling'

const props = defineProps<{
    schedules: ScheduleCollection
    conflictsCount: number
}>()

const emit = defineEmits<{ openConflicts: [] }>()

const professorCount = computed(() => new Set(props.schedules.map((s) => s.professor.id)).size)
const classroomCount = computed(() => new Set(props.schedules.map((s) => s.classroom.id)).size)

import { computed } from 'vue'
</script>

<template>
    <div class="sch-stats">
        <div class="stat">
            <div class="stat-ico">📅</div>
            <div class="stat-info">
                <div class="v">{{ schedules.length }}</div>
                <div class="l">Clases programadas</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-ico">👤</div>
            <div class="stat-info">
                <div class="v">{{ professorCount }}</div>
                <div class="l">Profesores activos</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-ico">🏫</div>
            <div class="stat-info">
                <div class="v">{{ classroomCount }}</div>
                <div class="l">Aulas en uso</div>
            </div>
        </div>
        <button
            type="button"
            class="stat"
            :class="{ warn: conflictsCount > 0, clickable: conflictsCount > 0 }"
            :disabled="conflictsCount === 0"
            @click="conflictsCount > 0 && emit('openConflicts')"
        >
            <div class="stat-ico">{{ conflictsCount > 0 ? '⚠' : '✓' }}</div>
            <div class="stat-info">
                <div class="v">{{ conflictsCount }}</div>
                <div class="l">
                    Conflictos detectados
                    <span v-if="conflictsCount > 0" class="cta"> Ver →</span>
                </div>
            </div>
        </button>
    </div>
</template>

<style scoped>
.sch-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 14px;
}
.stat {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: inherit;
    text-align: left;
    width: 100%;
}
button.stat { cursor: default; }
button.stat:disabled { opacity: 1; }
button.stat.warn.clickable { cursor: pointer; }
button.stat.warn.clickable:hover { border-color: var(--danger); background: color-mix(in srgb, var(--danger) 4%, var(--bg-surface)); }

.stat-ico {
    width: 34px; height: 34px;
    border-radius: var(--r-sm);
    display: grid; place-items: center;
    flex-shrink: 0;
    background: var(--bg-surface-2);
    color: var(--text-secondary);
    font-size: 16px;
}
.warn .stat-ico { background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger); }
.v { font-size: 18px; font-weight: 600; letter-spacing: -0.01em; font-variant-numeric: tabular-nums; line-height: 1.1; }
.warn .v { color: var(--danger); }
.l { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }
.cta { margin-left: 6px; color: var(--danger); font-weight: 600; font-size: 11px; }

@media (max-width: 1024px) { .sch-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 540px)  { .sch-stats { grid-template-columns: 1fr 1fr; } }
</style>
```

- [ ] **Step 2: Create ScheduleLegend.vue**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import type { ScheduleCollection } from '@/types/scheduling'
import { careerColor } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: ScheduleCollection
    activeCareerIds: Set<number>
}>()

const emit = defineEmits<{ toggle: [careerId: number] }>()

const careers = computed(() => {
    const map = new Map<number, string>()
    for (const s of props.schedules) {
        if (s.career) map.set(s.career.id, s.career.name)
    }
    return [...map.entries()].map(([id, name]) => ({ id, name, color: careerColor(id) }))
})
</script>

<template>
    <div v-if="careers.length > 0" class="sch-legend">
        <span class="legend-label">Carreras</span>
        <button
            v-for="c in careers"
            :key="c.id"
            class="legend-item"
            :class="{ muted: !activeCareerIds.has(c.id) }"
            @click="emit('toggle', c.id)"
        >
            <span class="swatch" :style="{ background: c.color }" />
            {{ c.name }}
        </button>
    </div>
</template>

<style scoped>
.sch-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 10px 14px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    margin-bottom: 14px;
}
.legend-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 600;
    margin-right: 4px;
}
.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--text-secondary);
    padding: 3px 10px 3px 8px;
    border-radius: 999px;
    background: var(--bg-surface-2);
    border: 1px solid var(--border);
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.12s, background 0.12s;
}
.legend-item:hover { border-color: var(--border-strong); }
.legend-item.muted { opacity: 0.45; }
.swatch { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
</style>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/scheduling/ScheduleStats.vue resources/js/components/scheduling/ScheduleLegend.vue
git commit -m "feat(scheduling): add ScheduleStats and ScheduleLegend components"
```

---

## Task 10: Create ScheduleConflictsBanner and ScheduleToolbar

**Files:**
- Create: `resources/js/components/scheduling/ScheduleConflictsBanner.vue`
- Create: `resources/js/components/scheduling/ScheduleToolbar.vue`

- [ ] **Step 1: Create ScheduleConflictsBanner.vue**

```vue
<script setup lang="ts">
import type { ScheduleCollection } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { DAY_ABBRS, DAY_KEYS } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: ScheduleCollection
    conflicts: Map<number, Conflict>
}>()

const conflictingSchedules = computed(() =>
    props.schedules.filter((s) => props.conflicts.has(s.id))
)

import { computed } from 'vue'
</script>

<template>
    <div v-if="conflictingSchedules.length > 0" class="conflicts-banner">
        <div class="ico">⚠</div>
        <div class="body">
            <div class="title">{{ conflictingSchedules.length }} conflicto(s) detectados esta semana</div>
            <div class="list">
                <div v-for="s in conflictingSchedules" :key="s.id">
                    <span class="code">{{ DAY_ABBRS[DAY_KEYS.indexOf(s.dayOfWeek)] }} {{ s.startTime }}</span>
                    · <strong>{{ s.subject.name }}</strong>
                    — {{ conflicts.get(s.id)!.reason }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.conflicts-banner {
    background: color-mix(in srgb, var(--danger) 5%, var(--bg-surface));
    border: 1px solid color-mix(in srgb, var(--danger) 25%, var(--border));
    border-radius: var(--r-md);
    padding: 12px 14px;
    margin-bottom: 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.ico {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--danger) 15%, transparent);
    color: var(--danger);
    display: grid; place-items: center;
    flex-shrink: 0;
    font-size: 14px;
}
.body { flex: 1; min-width: 0; }
.title { font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.list  { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }
.code  { font-size: 11px; color: var(--danger); background: var(--bg-surface-2); border: 1px solid var(--border); padding: 0 5px; border-radius: 3px; }
</style>
```

- [ ] **Step 2: Create ScheduleToolbar.vue**

```vue
<script setup lang="ts">
import type { ScheduleAvailablePeriod, ScheduleAvailableSection, ScheduleAvailableProfessor } from '@/types/scheduling'

const props = defineProps<{
    view: 'week' | 'day' | 'list'
    query: string
    periodId: number | null
    sectionId: number | null
    professorId: number | null
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    canCreate?: boolean
}>()

const emit = defineEmits<{
    'update:view':       ['week' | 'day' | 'list']
    'update:query':      [string]
    'update:periodId':   [number | null]
    'update:sectionId':  [number | null]
    'update:professorId':[number | null]
    create:              []
    applyFilters:        []
}>()

const activePeriodName   = computed(() => props.periods.find((p) => p.id === props.periodId)?.name   ?? null)
const activeSectionCode  = computed(() => props.sections.find((s) => s.id === props.sectionId)?.code  ?? null)
const activeProfName     = computed(() => props.professors.find((p) => p.id === props.professorId)?.name ?? null)

import { computed } from 'vue'
</script>

<template>
    <div>
        <!-- Toolbar row -->
        <div class="sch-toolbar">
            <!-- View switcher -->
            <div class="sch-views">
                <button :class="{ active: view === 'week' }"  @click="emit('update:view', 'week')">⊞ Semana</button>
                <button :class="{ active: view === 'day' }"   @click="emit('update:view', 'day')">▭ Día</button>
                <button :class="{ active: view === 'list' }"  @click="emit('update:view', 'list')">☰ Lista</button>
            </div>

            <div class="sch-toolbar-spacer" />

            <!-- Search -->
            <div class="sch-search">
                <span class="icon">⌕</span>
                <input
                    :value="query"
                    type="search"
                    placeholder="Buscar asignatura, profesor, aula…"
                    @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                />
            </div>

            <!-- Create button -->
            <button v-if="canCreate" class="btn primary" @click="emit('create')">
                + Nuevo horario
            </button>
        </div>

        <!-- Filter chips row -->
        <div v-if="activePeriodName || activeSectionCode || activeProfName" class="sch-filters">
            <span v-if="activePeriodName" class="filter-chip">
                <span class="key">Período:</span>
                <span class="val">{{ activePeriodName }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:periodId', null); emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeSectionCode" class="filter-chip">
                <span class="key">Sección:</span>
                <span class="val">{{ activeSectionCode }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:sectionId', null); emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeProfName" class="filter-chip">
                <span class="key">Profesor:</span>
                <span class="val">{{ activeProfName }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:professorId', null); emit('applyFilters')">✕</button>
            </span>
            <button
                class="filters-clear"
                @click="emit('update:periodId', null); emit('update:sectionId', null); emit('update:professorId', null); emit('applyFilters')"
            >
                Limpiar todos
            </button>
        </div>
    </div>
</template>

<style scoped>
.sch-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}
.sch-toolbar-spacer { flex: 1; }

.sch-views {
    display: inline-flex;
    background: var(--bg-surface-2);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 3px;
    gap: 2px;
}
.sch-views button {
    height: 28px;
    padding: 0 12px;
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: var(--r-sm);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.sch-views button:hover { color: var(--text-primary); }
.sch-views button.active { background: var(--bg-surface); color: var(--text-primary); box-shadow: var(--shadow-xs); }

.sch-search {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 34px;
    width: 220px;
}
.sch-search .icon { position: absolute; left: 10px; color: var(--text-muted); pointer-events: none; font-size: 15px; }
.sch-search input {
    width: 100%;
    height: 34px;
    padding: 0 12px 0 32px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-md);
    color: var(--text-primary);
    font-family: inherit;
    font-size: 13px;
}
.sch-search input::placeholder { color: var(--text-muted); }
.sch-search input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent); }

.btn.primary {
    height: 34px; padding: 0 16px;
    background: var(--accent); color: #fff;
    border: 0; border-radius: var(--r-md);
    font-family: inherit; font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn.primary:hover { background: color-mix(in srgb, var(--accent) 85%, #000); }

/* Filter chips */
.sch-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px 0; }
.filter-chip {
    display: inline-flex; align-items: center; gap: 6px;
    height: 28px; padding: 0 4px 0 10px;
    background: var(--bg-surface); border: 1px solid var(--border-strong);
    border-radius: 999px; font-size: 12px; color: var(--text-primary); font-family: inherit;
}
.filter-chip .key { color: var(--text-muted); font-weight: 500; }
.filter-chip .val { font-weight: 600; }
.filter-chip .x {
    width: 20px; height: 20px;
    border: 0; background: transparent; color: var(--text-muted);
    cursor: pointer; border-radius: 50%;
    display: grid; place-items: center; font-size: 11px;
}
.filter-chip .x:hover { background: var(--bg-sunken); color: var(--text-primary); }
.filters-clear {
    margin-left: auto; background: transparent; border: 0;
    color: var(--text-muted); font-size: 12px; font-family: inherit; cursor: pointer;
    text-decoration: underline; text-underline-offset: 2px;
}
.filters-clear:hover { color: var(--text-primary); }

@media (max-width: 820px) {
    .sch-search { width: 100%; order: 10; }
    .sch-toolbar-spacer { display: none; }
    .sch-views { width: 100%; order: 9; }
    .sch-views button { flex: 1; justify-content: center; height: 36px; }
}
</style>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/scheduling/ScheduleConflictsBanner.vue resources/js/components/scheduling/ScheduleToolbar.vue
git commit -m "feat(scheduling): add ScheduleConflictsBanner and ScheduleToolbar"
```

---

## Task 11: Update TypeScript types

**Files:**
- Modify: `resources/js/types/scheduling.ts`

- [ ] **Step 1: Add career to Schedule type**

In `resources/js/types/scheduling.ts`, find the `Schedule` type and add the `career` field:

```typescript
export type Schedule = {
    id: number
    section: ScheduleSection
    professor: ScheduleProfessor
    classroom: ScheduleClassroom
    subject: ScheduleSubject
    career: { id: number; name: string } | null   // ← add this line
    dayOfWeek: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday'
    dayLabel: string
    startTime: string
    endTime: string
    type: 'theory' | 'lab'
    typeLabel: string
    validFrom: string
    validUntil: string | null
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/scheduling.ts
git commit -m "feat(scheduling): add career field to Schedule TypeScript type"
```

---

## Task 12: Rewrite Index.vue

**Files:**
- Modify: `resources/js/pages/scheduling/Schedules/Index.vue`

- [ ] **Step 1: Replace the entire file content**

```vue
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import ScheduleListView from '@/components/scheduling/ScheduleListView.vue'
import ScheduleClusterPopover from '@/components/scheduling/ScheduleClusterPopover.vue'
import ScheduleStats from '@/components/scheduling/ScheduleStats.vue'
import ScheduleLegend from '@/components/scheduling/ScheduleLegend.vue'
import ScheduleConflictsBanner from '@/components/scheduling/ScheduleConflictsBanner.vue'
import ScheduleToolbar from '@/components/scheduling/ScheduleToolbar.vue'
import CreateScheduleModal from '@/components/scheduling/CreateScheduleModal.vue'
import EditScheduleModal from '@/components/scheduling/EditScheduleModal.vue'
import DeleteScheduleModal from '@/components/scheduling/DeleteScheduleModal.vue'
import { useScheduleFilters } from '@/composables/filters/useScheduleFilters'
import { useSchedulePermissions } from '@/composables/permissions/useSchedulePermissions'
import { detectConflicts, todayKey, DAY_KEYS } from '@/composables/scheduling/useScheduleLayout'
import { index } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableClassroom,
    ScheduleAvailablePeriod,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
    ScheduleCollection,
} from '@/types/scheduling'
import type { PopoverData } from '@/components/scheduling/ScheduleClusterPopover.vue'

type Props = {
    schedules: ScheduleCollection
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    filters: { period_id: number | null; section_id: number | null; professor_id: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Horarios', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSchedulePermissions()
const { periodId, sectionId, professorId, applyFilters } = useScheduleFilters(
    props.filters.period_id,
    props.filters.section_id,
    props.filters.professor_id,
)

// View state
const view      = ref<'week' | 'day' | 'list'>('week')
const mobileDay = ref(DAY_KEYS.indexOf(todayKey() ?? 'monday'))
const searchQuery       = ref('')
const activeCareerIds   = ref(new Set<number>())

// Initialise activeCareerIds with all careers in the dataset
const allCareerIds = computed(() => {
    const ids = new Set<number>()
    for (const s of props.schedules) {
        if (s.career) ids.add(s.career.id)
    }
    return ids
})

// Toggle a career filter (if set not yet populated, first show all)
function toggleCareer(id: number): void {
    if (activeCareerIds.value.size === 0) {
        // Populate with all then remove the clicked one
        activeCareerIds.value = new Set(allCareerIds.value)
    }
    if (activeCareerIds.value.has(id)) {
        activeCareerIds.value.delete(id)
    } else {
        activeCareerIds.value.add(id)
    }
    activeCareerIds.value = new Set(activeCareerIds.value) // trigger reactivity
}

// Client-side filtered schedules
const filteredSchedules = computed(() => {
    const q = searchQuery.value.toLowerCase().trim()
    return props.schedules.filter((s) => {
        // Career filter (empty set = show all)
        if (activeCareerIds.value.size > 0 && s.career && !activeCareerIds.value.has(s.career.id)) {
            return false
        }
        // Text search
        if (q) {
            return (
                s.subject.name.toLowerCase().includes(q) ||
                s.subject.code.toLowerCase().includes(q) ||
                s.professor.user.name.toLowerCase().includes(q) ||
                s.classroom.identifier.toLowerCase().includes(q) ||
                s.section.code.toLowerCase().includes(q)
            )
        }
        return true
    })
})

const conflicts = computed(() => detectConflicts(filteredSchedules.value))

// Modal state
const showCreate       = ref(false)
const createDefaults   = ref<{ dayOfWeek?: string; startTime?: string }>({})
const editingSchedule  = ref<Schedule | null>(null)
const deletingSchedule = ref<Schedule | null>(null)
const popoverData      = ref<PopoverData | null>(null)

const sectionsForPeriod = computed(() =>
    periodId.value ? props.sections.filter((s) => s.periodId === periodId.value) : props.sections
)

// Grid event handlers
function handleCreateFromGrid(defaults: { dayOfWeek: string; startTime: string }): void {
    createDefaults.value = defaults
    showCreate.value = true
}

function handleOpenEvent(schedule: Schedule): void {
    popoverData.value = {
        kind: 'event',
        schedule,
        conflict: conflicts.value.get(schedule.id),
    }
}

function handleOpenCluster(data: { dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }): void {
    popoverData.value = { kind: 'cluster', ...data }
}

function handleOpenConflicts(): void {
    popoverData.value = {
        kind: 'conflicts',
        schedules: filteredSchedules.value.filter((s) => conflicts.value.has(s.id)),
    }
}

// Popover → modal escalation
function handleEditFromPopover(schedule: Schedule): void {
    popoverData.value = null
    editingSchedule.value = schedule
}

function handleDeleteFromPopover(schedule: Schedule): void {
    popoverData.value = null
    deletingSchedule.value = schedule
}
</script>

<template>
    <Head title="Horarios" />

    <div style="display:flex;flex-direction:column;gap:0;">

        <!-- Page header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-bottom:16px;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Horarios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Cuadrícula semanal de clases · período activo
                </p>
            </div>
        </div>

        <!-- Toolbar + filter chips -->
        <ScheduleToolbar
            :view="view"
            :query="searchQuery"
            :period-id="periodId"
            :section-id="sectionId"
            :professor-id="professorId"
            :periods="periods"
            :sections="sectionsForPeriod"
            :professors="professors"
            :can-create="canCreate"
            style="margin-bottom:16px;"
            @update:view="view = $event"
            @update:query="searchQuery = $event"
            @update:period-id="periodId = $event"
            @update:section-id="sectionId = $event"
            @update:professor-id="professorId = $event"
            @create="showCreate = true"
            @apply-filters="applyFilters"
        />

        <!-- Stats -->
        <ScheduleStats
            :schedules="filteredSchedules"
            :conflicts-count="conflicts.size"
            @open-conflicts="handleOpenConflicts"
        />

        <!-- Career legend -->
        <ScheduleLegend
            :schedules="filteredSchedules"
            :active-career-ids="activeCareerIds.size === 0 ? allCareerIds : activeCareerIds"
            @toggle="toggleCareer"
        />

        <!-- Conflicts inline banner -->
        <ScheduleConflictsBanner :schedules="filteredSchedules" :conflicts="conflicts" />

        <!-- Calendar / List view -->
        <div class="card" style="padding:0;overflow:hidden;">
            <WeeklyGrid
                v-if="view !== 'list'"
                :schedules="filteredSchedules"
                :conflicts="conflicts"
                :can-update="canUpdate"
                :can-delete="canDelete"
                :mobile-day="mobileDay"
                :day-mode="view === 'day'"
                @create="handleCreateFromGrid"
                @open-event="handleOpenEvent"
                @open-cluster="handleOpenCluster"
                @edit-schedule="editingSchedule = $event"
                @delete-schedule="deletingSchedule = $event"
                @update:mobile-day="mobileDay = $event"
            />
            <ScheduleListView
                v-else
                :schedules="filteredSchedules"
                :conflicts="conflicts"
                :can-update="canUpdate"
                :can-delete="canDelete"
                @open-event="handleOpenEvent"
                @edit-schedule="editingSchedule = $event"
                @delete-schedule="deletingSchedule = $event"
            />
        </div>
    </div>

    <!-- Cluster / event popover -->
    <ScheduleClusterPopover
        :data="popoverData"
        :conflicts="conflicts"
        @close="popoverData = null"
        @edit-schedule="handleEditFromPopover"
        @delete-schedule="handleDeleteFromPopover"
    />

    <!-- CRUD modals (existing, unchanged) -->
    <CreateScheduleModal
        :open="showCreate"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        :default-section-id="sectionId"
        :default-day-of-week="createDefaults.dayOfWeek"
        :default-start-time="createDefaults.startTime"
        @update:open="showCreate = $event"
    />

    <EditScheduleModal
        v-if="editingSchedule"
        :schedule="editingSchedule"
        :open="editingSchedule !== null"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        @update:open="editingSchedule = $event ? editingSchedule : null"
    />

    <DeleteScheduleModal
        v-if="deletingSchedule"
        :schedule="deletingSchedule"
        :open="deletingSchedule !== null"
        @update:open="deletingSchedule = $event ? deletingSchedule : null"
    />
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/pages/scheduling/Schedules/Index.vue
git commit -m "feat(scheduling): rewrite Index.vue with full calendar design"
```

---

## Task 13: Final validation — pint + build + tests

- [ ] **Step 1: Run pint on all modified PHP files**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Expected: no diff remaining.

- [ ] **Step 2: Run the full test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass, including the new ScheduleResourceCareerTest.

- [ ] **Step 3: Build frontend assets**

```bash
vendor/bin/sail npm run build
```

Expected: builds cleanly without TypeScript errors.

- [ ] **Step 4: Final commit**

```bash
git add -p   # review any residual changes
git commit -m "chore(scheduling): pint format and asset build"
```

---

## Self-Review Checklist

- [x] **Spec coverage**: Toolbar ✓, filter chips ✓, stats row ✓, career legend ✓, conflicts banner ✓, week grid with overlap layout ✓, day view ✓, list view ✓, cluster popover ✓, event detail popover ✓, "now" line ✓, mobile navigation ✓, existing CRUD modals preserved ✓
- [x] **Placeholder scan**: No TBDs or incomplete implementations — all code is concrete
- [x] **Type consistency**: `PopoverData` exported from `ScheduleClusterPopover.vue` and imported in `Index.vue`; `Conflict` type exported from composable and used consistently across all components; `Schedule.career` is `{ id: number; name: string } | null` in both PHP resource and TS types
- [x] **No breaking changes**: existing `CreateScheduleModal`, `EditScheduleModal`, `DeleteScheduleModal` are preserved and rewired; the `useScheduleFilters` composable is reused unchanged; route unchanged
