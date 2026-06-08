<script setup lang="ts">
import { computed } from 'vue'
import { toMinutes } from '@/composables/scheduling/useScheduleLayout'
import type {
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'
import { enrollmentColor } from '@/utils/enrollmentColor'

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

        if (!subject) {
continue
}

        const section = subject.sections[secIdx]

        if (!section?.slots.length) {
continue
}

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
