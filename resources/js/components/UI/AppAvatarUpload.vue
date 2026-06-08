<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    src?: string
    name?: string
}>()

const emit = defineEmits<{
    upload: []
    remove: []
}>()

const initials = computed(() => {
    if (!props.name) {
return ''
}

    return props.name
        .split(' ')
        .slice(0, 2)
        .map(w => w[0]?.toUpperCase() ?? '')
        .join('')
})
</script>

<template>
    <div class="uf-avatar-upload">
        <div class="uf-avatar-preview">
            <img v-if="src" :src="src" alt="Avatar" />
            <span v-else-if="initials" class="uf-avatar-initials">{{ initials }}</span>
            <svg v-else width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div class="uf-avatar-actions">
            <button type="button" class="uf-avatar-btn" @click="emit('upload')">
                Subir foto
            </button>
            <button v-if="src" type="button" class="uf-avatar-btn danger" @click="emit('remove')">
                Eliminar
            </button>
        </div>
    </div>
</template>
