<script setup lang="ts">
import type { Schedule } from '@/types/scheduling'

const props = defineProps<{
    schedule: Schedule
    canUpdate: boolean
    canDelete: boolean
}>()

const emit = defineEmits<{
    edit: [schedule: Schedule]
}>()

function timeToMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number)

    return h * 60 + m
}

const GRID_START_MINUTES = 7 * 60

const top    = timeToMinutes(props.schedule.startTime) - GRID_START_MINUTES
const height = timeToMinutes(props.schedule.endTime) - timeToMinutes(props.schedule.startTime)

const bgColor = props.schedule.type === 'theory'
    ? 'var(--color-info-subtle, #dbeafe)'
    : 'var(--color-success-subtle, #dcfce7)'

const borderColor = props.schedule.type === 'theory'
    ? 'var(--color-info, #3b82f6)'
    : 'var(--color-success, #22c55e)'
</script>

<template>
    <div
        :style="{
            position: 'absolute',
            top: top + 'px',
            height: height + 'px',
            left: '2px',
            right: '2px',
            backgroundColor: bgColor,
            borderLeft: '3px solid ' + borderColor,
            borderRadius: '4px',
            padding: '2px 4px',
            overflow: 'hidden',
            cursor: canUpdate || canDelete ? 'pointer' : 'default',
            zIndex: 1,
        }"
        @click="canUpdate || canDelete ? emit('edit', schedule) : undefined"
    >
        <div style="font-size:11px;font-weight:600;line-height:1.2;color:var(--text-primary);">
            {{ schedule.subject.code }}
        </div>
        <div v-if="height >= 30" style="font-size:10px;color:var(--text-secondary);line-height:1.2;">
            {{ schedule.professor.user.name }}
        </div>
        <div v-if="height >= 45" style="font-size:10px;color:var(--text-muted);line-height:1.2;">
            {{ schedule.classroom.identifier }}
        </div>
    </div>
</template>
