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
