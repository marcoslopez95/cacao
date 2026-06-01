<script setup lang="ts">
defineProps<{
    items: unknown[]
    addLabel?: string
    min?: number
}>()

const emit = defineEmits<{
    add: []
    remove: [number]
}>()
</script>

<template>
    <div class="uf-repeatable">
        <div
            v-for="(item, index) in items"
            :key="((item as Record<string, unknown>).__id as string | number) ?? index"
            class="uf-repeatable-item"
        >
            <div class="uf-repeatable-header">
                <span class="uf-repeatable-num">{{ index + 1 }}</span>
                <button
                    v-if="!min || items.length > min"
                    type="button"
                    class="uf-repeatable-remove"
                    @click="emit('remove', index)"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <slot name="item" :item="item" :index="index" />
        </div>
        <button type="button" class="uf-repeatable-add" dusk="repeatable-add" @click="emit('add')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ addLabel ?? 'Agregar' }}
        </button>
    </div>
</template>
