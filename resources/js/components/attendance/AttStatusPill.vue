<script setup lang="ts">
import { computed } from 'vue'
import type { ClassSessionStatus } from '@/types/attendance'

const props = defineProps<{
    status: ClassSessionStatus
}>()

type ToneConfig = {
    bg: string
    fg: string
    label: string
}

const toneMap: Record<ClassSessionStatus, ToneConfig> = {
    held:      { bg: 'var(--success-bg)',    fg: 'var(--success-fg)',    label: 'Dada' },
    scheduled: { bg: 'var(--warning-bg)',    fg: 'var(--warning-fg)',    label: 'Pendiente' },
    cancelled: { bg: 'var(--danger-bg)',     fg: 'var(--danger-fg)',     label: 'Cancelada' },
    recovered: { bg: 'var(--bg-surface-2)',  fg: 'var(--text-secondary)', label: 'Recuperada' },
    advanced:  { bg: 'var(--info-bg)',       fg: 'var(--info-fg)',       label: 'Adelantada' },
}

const tone = computed(() => toneMap[props.status] ?? toneMap.scheduled)

const pillStyle = computed(() => ({
    backgroundColor: tone.value.bg,
    color: tone.value.fg,
}))
</script>

<template>
    <span
        :style="pillStyle"
        class="inline-flex items-center gap-1.5 border border-transparent rounded-full px-2 py-0.5"
        style="font-size: 10.5px; font-weight: 600;"
    >
        <span
            class="rounded-full bg-current shrink-0"
            style="width: 6px; height: 6px;"
        />
        {{ tone.label }}
    </span>
</template>
