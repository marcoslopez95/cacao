<script setup lang="ts">
withDefaults(defineProps<{
    variant?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent'
    dot?: boolean
}>(), {
    variant: 'neutral',
})
</script>

<template>
    <span :class="['badge', `badge-${variant}`]">
        <span v-if="dot" class="bdot" />
        <slot />
    </span>
</template>
