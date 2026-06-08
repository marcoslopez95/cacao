<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    date: string // 'YYYY-MM-DD'
}>()

const DOW_LABELS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb']
const MON_LABELS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

const parsed = computed(() => {
    const parts = props.date.split('-').map(Number) // [y, m, d]
    const dt = new Date(parts[0], parts[1] - 1, parts[2])

    return {
        dow: DOW_LABELS[dt.getDay()],
        day: parts[2],
        mon: MON_LABELS[parts[1] - 1],
    }
})
</script>

<template>
    <div
        class="flex flex-col items-center border overflow-hidden shrink-0"
        style="width: 46px; border-radius: var(--radius-md);"
    >
        <!-- Day of week -->
        <div
            class="w-full text-center uppercase tracking-wider"
            style="
                background-color: var(--bg-surface-2);
                color: var(--text-muted);
                font-size: 9px;
                padding: 2px 0;
            "
        >
            {{ parsed.dow }}
        </div>

        <!-- Day number -->
        <div
            class="w-full text-center font-bold"
            style="
                color: var(--text-primary);
                font-size: 19px;
                line-height: 1.2;
                padding: 2px 0;
            "
        >
            {{ parsed.day }}
        </div>

        <!-- Month -->
        <div
            class="w-full text-center uppercase tracking-wider"
            style="
                color: var(--text-muted);
                font-size: 9px;
                padding-bottom: 4px;
            "
        >
            {{ parsed.mon }}
        </div>
    </div>
</template>
