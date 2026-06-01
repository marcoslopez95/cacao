<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    present: number
    absent: number
}>()

const total = computed(() => props.present + props.absent)

const presentPct = computed(() =>
    total.value === 0 ? 0 : (props.present / total.value) * 100,
)

const absentPct = computed(() =>
    total.value === 0 ? 0 : (props.absent / total.value) * 100,
)
</script>

<template>
    <div class="w-full">
        <!-- Bar -->
        <div
            class="w-full flex overflow-hidden"
            style="height: 8px; border-radius: var(--radius-pill);"
        >
            <template v-if="total > 0">
                <div
                    :style="{ width: `${presentPct}%`, backgroundColor: 'var(--success)' }"
                    style="transition: width 300ms ease;"
                />
                <div
                    :style="{ width: `${absentPct}%`, backgroundColor: 'var(--danger)' }"
                    style="transition: width 300ms ease;"
                />
            </template>
            <template v-else>
                <div class="w-full" style="background-color: var(--bg-surface-2);" />
            </template>
        </div>

        <!-- Legend -->
        <div
            class="flex items-center gap-3 mt-1.5"
            style="font-size: 11px; color: var(--text-muted);"
        >
            <span class="flex items-center gap-1">
                <span
                    class="rounded-full shrink-0"
                    style="width: 7px; height: 7px; background-color: var(--success);"
                />
                {{ present }} presente
            </span>
            <span class="flex items-center gap-1">
                <span
                    class="rounded-full shrink-0"
                    style="width: 7px; height: 7px; background-color: var(--danger);"
                />
                {{ absent }} ausente
            </span>
        </div>
    </div>
</template>
