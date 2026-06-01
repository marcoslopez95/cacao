<script setup lang="ts">
import { computed } from 'vue'
import type { ScheduleAvailableCareer } from '@/types/scheduling'
import { careerColor } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    careers: ScheduleAvailableCareer[]
    activeCareerIds: Set<number>
}>()

const emit = defineEmits<{ toggle: [careerId: number] }>()

const careersWithColor = computed(() =>
    props.careers.map((c) => ({ ...c, color: careerColor(c.id) })),
)
</script>

<template>
    <div v-if="careersWithColor.length > 0" class="sch-legend">
        <span class="legend-label">Carreras</span>
        <button
            v-for="c in careersWithColor"
            :key="c.id"
            dusk="legend-career-btn"
            class="legend-item"
            :class="{ muted: !activeCareerIds.has(c.id) }"
            @click="emit('toggle', c.id)"
        >
            <span class="swatch" :style="{ background: c.color }" />
            {{ c.name }}
        </button>
    </div>
</template>

<style scoped>
.sch-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 10px 14px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    margin-bottom: 14px;
}
.legend-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 600;
    margin-right: 4px;
}
.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--text-secondary);
    padding: 3px 10px 3px 8px;
    border-radius: 999px;
    background: var(--bg-surface-2);
    border: 1px solid var(--border);
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.12s, background 0.12s;
}
.legend-item:hover { border-color: var(--border-strong); }
.legend-item.muted { opacity: 0.45; }
.swatch { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
</style>
