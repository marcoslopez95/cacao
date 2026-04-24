<script setup lang="ts">
withDefaults(defineProps<{
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    variant?: 'default' | 'on-light' | 'on-topbar'
}>(), {
    size: 'md',
    variant: 'default',
})

const sizeMap = { xs: 'iso-20', sm: 'iso-28', md: 'iso-40', lg: 'iso-72', xl: 'iso-120' }
const variantMap = { default: '', 'on-light': 'iso-on-light', 'on-topbar': 'iso-on-topbar' }
</script>

<template>
    <div
        :class="['iso', sizeMap[size], variantMap[variant]]"
        role="img"
        aria-label="CACAO isotipo"
    >
        <span class="cell" />
        <span class="cell" />
        <span class="cell accent" />
        <span class="cell" />
        <span class="cell" />
        <span class="cell empty" />
        <span class="cell" />
        <span class="cell" />
        <span class="cell" />
    </div>
</template>
