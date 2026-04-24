<script setup lang="ts">
defineProps<{ hover?: boolean }>()
</script>

<template>
    <div class="card">
        <div v-if="$slots.header" class="card-head">
            <slot name="header" />
        </div>
        <div class="card-body">
            <slot />
        </div>
        <div v-if="$slots.footer" class="card-foot">
            <slot name="footer" />
        </div>
    </div>
</template>
