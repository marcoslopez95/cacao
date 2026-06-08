<script setup lang="ts">
import { computed } from 'vue'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import { DAY_ABBRS, DAY_KEYS } from '@/composables/scheduling/useScheduleLayout'
import type { ScheduleCollection } from '@/types/scheduling'

const props = defineProps<{
    schedules: ScheduleCollection
    conflicts: Map<number, Conflict>
}>()

const conflictingSchedules = computed(() =>
    props.schedules.filter((s) => props.conflicts.has(s.id))
)
</script>

<template>
    <div v-if="conflictingSchedules.length > 0" class="conflicts-banner">
        <div class="ico">⚠</div>
        <div class="body">
            <div class="title">{{ conflictingSchedules.length }} conflicto(s) detectados esta semana</div>
            <div class="list">
                <div v-for="s in conflictingSchedules" :key="s.id">
                    <span class="code">{{ DAY_ABBRS[DAY_KEYS.indexOf(s.dayOfWeek)] }} {{ s.startTime }}</span>
                    · <strong>{{ s.subject.name }}</strong>
                    — {{ conflicts.get(s.id)!.reason }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.conflicts-banner {
    background: color-mix(in srgb, var(--danger) 5%, var(--bg-surface));
    border: 1px solid color-mix(in srgb, var(--danger) 25%, var(--border));
    border-radius: var(--r-md);
    padding: 12px 14px;
    margin-bottom: 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.ico {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--danger) 15%, transparent);
    color: var(--danger);
    display: grid; place-items: center;
    flex-shrink: 0;
    font-size: 14px;
}
.body { flex: 1; min-width: 0; }
.title { font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.list  { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }
.code  { font-size: 11px; color: var(--danger); background: var(--bg-surface-2); border: 1px solid var(--border); padding: 0 5px; border-radius: 3px; }
</style>
