<script setup lang="ts">
import { ref } from 'vue'
import Icon from '@/components/base/Icon.vue'

withDefaults(defineProps<{
    variant?: 'success' | 'warning' | 'danger' | 'info'
    title?: string
    dismissible?: boolean
}>(), {
    variant: 'info',
    dismissible: false,
})

const visible = ref(true)

const icons = { success: 'check', warning: 'alert', danger: 'alert', info: 'info' }
</script>

<template>
    <div v-if="visible" :class="['alert', `alert-${variant}`]">
        <Icon :name="icons[variant]" :size="16" style="flex-shrink:0;margin-top:1px;" />
        <div style="flex:1;">
            <strong v-if="title" style="display:block;margin-bottom:2px;font-weight:600;">{{ title }}</strong>
            <slot />
        </div>
        <button
            v-if="dismissible"
            class="btn btn-ghost btn-icon btn-sm"
            style="flex-shrink:0;align-self:flex-start;"
            aria-label="Cerrar"
            @click="visible = false"
        >
            <Icon name="x" :size="14" />
        </button>
    </div>
</template>
