<script setup lang="ts">
import { computed } from 'vue'
import ScheduleSlot from '@/components/scheduling/ScheduleSlot.vue'
import type { Schedule, ScheduleCollection } from '@/types/scheduling'

const props = defineProps<{
    schedules: ScheduleCollection
    canUpdate: boolean
    canDelete: boolean
}>()

const emit = defineEmits<{
    create: [{ dayOfWeek: string; startTime: string }]
    edit: [schedule: Schedule]
}>()

const DAYS = [
    { key: 'monday',    label: 'Lunes' },
    { key: 'tuesday',   label: 'Martes' },
    { key: 'wednesday', label: 'Miércoles' },
    { key: 'thursday',  label: 'Jueves' },
    { key: 'friday',    label: 'Viernes' },
    { key: 'saturday',  label: 'Sábado' },
]

const HOURS = Array.from({ length: 11 }, (_, i) => {
    const h = 7 + i
    return `${String(h).padStart(2, '0')}:00`
})

const GRID_HEIGHT = 660

function schedulesForDay(day: string): Schedule[] {
    return props.schedules.filter((s) => s.dayOfWeek === day)
}

function handleCellClick(day: string): void {
    if (props.canUpdate) {
        emit('create', { dayOfWeek: day, startTime: '08:00' })
    }
}
</script>

<template>
    <div style="overflow-x:auto;">
        <div style="display:grid;grid-template-columns:56px repeat(6, 1fr);min-width:680px;">
            <!-- Header row -->
            <div style="border-bottom:1px solid var(--border);padding:8px 4px;" />
            <div
                v-for="day in DAYS"
                :key="day.key"
                style="border-bottom:1px solid var(--border);border-left:1px solid var(--border);padding:8px 4px;text-align:center;font-size:var(--text-sm);font-weight:600;color:var(--text-primary);"
            >
                {{ day.label }}
            </div>

            <!-- Body: time labels + day columns -->
            <div :style="{ position: 'relative', height: GRID_HEIGHT + 'px' }">
                <div
                    v-for="(hour, i) in HOURS"
                    :key="hour"
                    :style="{
                        position: 'absolute',
                        top: (i * 60) + 'px',
                        right: '4px',
                        fontSize: '10px',
                        color: 'var(--text-muted)',
                        lineHeight: '1',
                        userSelect: 'none',
                    }"
                >
                    {{ hour }}
                </div>
            </div>

            <div
                v-for="day in DAYS"
                :key="day.key + '-col'"
                :style="{
                    position: 'relative',
                    height: GRID_HEIGHT + 'px',
                    borderLeft: '1px solid var(--border)',
                    cursor: canUpdate ? 'pointer' : 'default',
                }"
                @click.self="handleCellClick(day.key)"
            >
                <!-- Hour lines -->
                <div
                    v-for="(_, i) in HOURS"
                    :key="i"
                    :style="{
                        position: 'absolute',
                        top: (i * 60) + 'px',
                        left: 0,
                        right: 0,
                        borderTop: i === 0 ? 'none' : '1px solid var(--border-subtle, rgba(0,0,0,0.05))',
                        pointerEvents: 'none',
                    }"
                />

                <!-- Schedule slots -->
                <ScheduleSlot
                    v-for="schedule in schedulesForDay(day.key)"
                    :key="schedule.id"
                    :schedule="schedule"
                    :can-update="canUpdate"
                    :can-delete="canDelete"
                    @edit="emit('edit', $event)"
                />
            </div>
        </div>
    </div>
</template>
