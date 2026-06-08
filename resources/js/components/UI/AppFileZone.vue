<script setup lang="ts">
defineProps<{
    label?: string
    accept?: string
    hint?: string
}>()

const emit = defineEmits<{ pick: [string] }>()

function handleClick(): void {
    emit('pick', 'demo.pdf')
}

function handleDrop(e: DragEvent): void {
    e.preventDefault()
    const file = e.dataTransfer?.files?.[0]

    if (file) {
emit('pick', file.name)
}
}
</script>

<template>
    <div
        class="uf-file-zone"
        role="button"
        tabindex="0"
        @click="handleClick"
        @keydown.enter="handleClick"
        @keydown.space.prevent="handleClick"
        @dragover.prevent
        @drop="handleDrop"
    >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <span class="uf-file-label">{{ label ?? 'Haz clic o arrastra un archivo' }}</span>
        <span v-if="hint" class="uf-file-hint">{{ hint }}</span>
    </div>
</template>
