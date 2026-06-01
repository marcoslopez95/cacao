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
            style="height: 6px; border-radius: var(--radius-pill);"
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

        <!-- Compact label -->
        <div
            class="mt-1"
            style="
                font-size: 11px;
                font-family: var(--font-mono);
                font-variant-numeric: tabular-nums;
                color: var(--text-muted);
            "
        >
            {{ present }}P &middot; {{ absent }}A
        </div>
    </div>
</template>
