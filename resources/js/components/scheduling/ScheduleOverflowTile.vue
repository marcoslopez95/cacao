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
        dusk="schedule-overflow-tile"
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
