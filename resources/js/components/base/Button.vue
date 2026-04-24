<script setup lang="ts">
import Icon from '@/components/base/Icon.vue'

withDefaults(defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'link' | 'tonal'
    size?: 'sm' | 'md' | 'lg'
    icon?: string
    iconRight?: string
    iconOnly?: boolean
    loading?: boolean
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
}>(), {
    variant: 'primary',
    size: 'md',
    type: 'button',
})

const iconSize = { sm: 13, md: 14, lg: 16 }
</script>

<template>
    <button
        :type="type"
        :class="['btn', `btn-${variant}`, `btn-${size}`, iconOnly ? 'btn-icon' : '']"
        :disabled="disabled || loading"
    >
        <span v-if="loading" class="spin" />
        <Icon v-else-if="icon" :name="icon" :size="iconSize[size]" />
        <slot v-if="!iconOnly" />
        <Icon v-if="!loading && iconRight" :name="iconRight" :size="iconSize[size]" />
    </button>
</template>
