<script setup lang="ts">
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { scheduleColor, scheduleHeight, scheduleTop, formatDuration, PX_PER_HOUR } from '@/composables/scheduling/useScheduleLayout'
import type { Schedule } from '@/types/scheduling'

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
// Content-box available when `compact` = height - (3px top + 3px bottom padding).
// Compact content stack = evt-title (12px * 1.15 ≈ 13.8px) + gap 2px + evt-compact-meta
// (9.5px * 1.2 ≈ 11.4px) = 27.2px, needing a content-box of >= 27.2px → height >= 33.2px.
// Below that, evt-compact-meta gets clipped mid-glyph instead of hidden cleanly, so we
// drop it entirely and show only the title once height falls under this threshold
// (e.g. 30-min classes: scheduleHeight 28px → rendered height 26px, well under 34px).
const veryCompact = compact && height < 34
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
        dusk="schedule-event-card"
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
        <div v-if="!compact" class="evt-time">
            {{ schedule.startTime }}–{{ schedule.endTime }}
            <span class="dur">{{ formatDuration(schedule) }}</span>
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
        <div v-else-if="!veryCompact" class="evt-compact-meta">
            <span class="time">{{ schedule.startTime }}–{{ schedule.endTime }}</span>
            <span class="sep">·</span>
            <span class="room">{{ schedule.classroom.identifier }}</span>
            <span class="sep">·</span>
            <span class="prof">{{ schedule.professor.user.name }}</span>
        </div>

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
.sch-evt.compact { padding: 3px 8px 3px 12px; gap: 2px; }
.sch-evt.compact .evt-title { -webkit-line-clamp: 1; font-size: 12px; line-height: 1.15; }
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

.evt-compact-meta {
    font-size: 9.5px;
    line-height: 1.2;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 3px;
    overflow: hidden;
    white-space: nowrap;
    flex-shrink: 0;
}
.evt-compact-meta .time {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    color: var(--text-secondary);
    flex-shrink: 0;
}
.evt-compact-meta .room {
    font-weight: 600;
    color: var(--text-secondary);
    flex-shrink: 0;
}
.evt-compact-meta .sep { opacity: 0.5; flex-shrink: 0; }
.evt-compact-meta .prof { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

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
@container (max-width: 80px)  { .evt-prof { display: none; } .evt-compact-meta { display: none; } .sch-evt { padding: 6px 6px 6px 10px; } }
</style>
