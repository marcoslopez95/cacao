<script setup lang="ts">
withDefaults(defineProps<{
    label?: string
    required?: boolean
    optional?: boolean
    adminOnly?: boolean
    help?: string
    error?: string
    ok?: string
    col?: number
}>(), {
    col: 6,
})
</script>

<template>
    <div class="uf-field" :class="`col-${col}`">
        <label v-if="label" class="uf-label">
            <span>{{ label }}</span>
            <span v-if="required" class="req">*</span>
            <span v-if="optional" class="opt">opcional</span>
            <span v-if="adminOnly" class="admin-only">solo admin</span>
        </label>
        <slot />
        <div v-if="error" class="uf-error">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 1 21h22z"/><path d="M12 10v5M12 18h.01"/></svg>
            {{ error }}
        </div>
        <div v-else-if="ok" class="uf-ok">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ ok }}
        </div>
        <div v-else-if="help" class="uf-help">{{ help }}</div>
    </div>
</template>
