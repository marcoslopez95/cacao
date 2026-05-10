<script setup lang="ts">
import { computed } from 'vue'
import type { ScheduleCollection } from '@/types/scheduling'

const props = defineProps<{
    schedules: ScheduleCollection
    conflictsCount: number
}>()

const emit = defineEmits<{ openConflicts: [] }>()

const professorCount = computed(() => new Set(props.schedules.map((s) => s.professor.id)).size)
const classroomCount = computed(() => new Set(props.schedules.map((s) => s.classroom.id)).size)
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
