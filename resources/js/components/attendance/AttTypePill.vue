<script setup lang="ts">
import type { ClassSessionType } from '@/types/attendance'

defineProps<{
    type: ClassSessionType
}>()

type TypeConfig = {
    borderColor: string
    bg: string
    fg: string
    label: string
}

const typeMap: Partial<Record<ClassSessionType, TypeConfig>> = {
    makeup:  {
        borderColor: 'var(--info)',
        bg: 'color-mix(in srgb, var(--info) 7%, transparent)',
        fg: 'var(--info-fg)',
        label: 'Recuperación',
    },
    advance: {
        borderColor: 'var(--warning)',
        bg: 'color-mix(in srgb, var(--warning) 7%, transparent)',
        fg: 'var(--warning-fg)',
        label: 'Adelanto',
    },
}
</script>

<template>
    <span
        v-if="type !== 'regular' && typeMap[type]"
        :style="{
            borderColor: typeMap[type]!.borderColor,
            backgroundColor: typeMap[type]!.bg,
            color: typeMap[type]!.fg,
        }"
        class="inline-flex items-center border rounded-full px-2 py-0.5"
        style="font-size: 10.5px; font-weight: 500;"
    >
        {{ typeMap[type]!.label }}
    </span>
</template>
