<script setup lang="ts">
withDefaults(defineProps<{
    label: string
    value: string | number
    delta?: string
    deltaDirection?: 'up' | 'down'
    footer?: string
    accent?: boolean
}>(), {
    accent: false,
})
</script>

<template>
    <div :class="['stat', accent ? 'accent' : '']">
        <div class="stat-label">{{ label }}</div>
        <div class="stat-value">{{ value }}</div>
        <div v-if="delta" :class="['stat-delta', deltaDirection ?? 'up']">
            {{ deltaDirection === 'up' ? '↑' : '↓' }} {{ delta }}
        </div>
        <div v-if="footer" class="stat-footer">{{ footer }}</div>
    </div>
</template>
