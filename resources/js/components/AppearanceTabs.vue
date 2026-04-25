<script setup lang="ts">
import Icon from '@/components/UI/AppIcon.vue'
import { useAppearance } from '@/composables/useAppearance'

const { appearance, updateAppearance } = useAppearance()

const tabs = [
    { value: 'light',  icon: 'sun',      label: 'Claro'   },
    { value: 'dark',   icon: 'moon',     label: 'Oscuro'  },
    { value: 'system', icon: 'settings', label: 'Sistema' },
] as const
</script>

<template>
    <div style="display:inline-flex;gap:4px;background:var(--bg-sunken);border-radius:var(--radius-md);padding:3px;">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            :class="['btn', 'btn-sm', appearance === tab.value ? 'btn-secondary' : 'btn-ghost']"
            @click="updateAppearance(tab.value)"
        >
            <Icon :name="tab.icon" :size="13" />
            {{ tab.label }}
        </button>
    </div>
</template>
