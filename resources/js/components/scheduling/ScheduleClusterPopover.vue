<script setup lang="ts">
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { scheduleColor, formatDuration, formatMinutes, DAY_LABELS } from '@/composables/scheduling/useScheduleLayout'
import type { Schedule } from '@/types/scheduling'

export type PopoverData =
    | { kind: 'event';     schedule: Schedule; conflict?: Conflict }
    | { kind: 'cluster';   dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }
    | { kind: 'conflicts'; schedules: Schedule[] }

defineProps<{
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
    if (!c) {
return ''
}

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
