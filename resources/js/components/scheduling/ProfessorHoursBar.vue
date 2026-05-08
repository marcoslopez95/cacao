<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    currentHours: number
    limitHours: number
}>()

const pct = computed(() => Math.min(100, (props.currentHours / props.limitHours) * 100))

const barColor = computed(() => {
    if (pct.value >= 100) return 'var(--color-danger, #ef4444)'
    if (pct.value >= 80)  return 'var(--color-warning, #f59e0b)'
    return 'var(--color-success, #22c55e)'
})
</script>

<template>
    <div style="display:flex;flex-direction:column;gap:4px;">
        <div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--text-secondary);">
            <span>Carga semanal</span>
            <span>{{ currentHours.toFixed(1) }} h de {{ limitHours }} h máx</span>
        </div>
        <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
            <div
                :style="{
                    height: '100%',
                    width: pct + '%',
                    backgroundColor: barColor,
                    borderRadius: '3px',
                    transition: 'width 0.2s',
                }"
            />
        </div>
    </div>
</template>
