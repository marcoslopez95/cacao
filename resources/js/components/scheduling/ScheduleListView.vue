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
