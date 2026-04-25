<script setup lang="ts">
import { nextTick, watch } from 'vue'
import Icon from '@/components/UI/AppIcon.vue'

const props = withDefaults(defineProps<{
    open: boolean
    title?: string
    description?: string
    size?: 'sm' | 'md' | 'lg'
    closeOnOverlay?: boolean
}>(), {
    size: 'md',
    closeOnOverlay: true,
})

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

function close(): void {
    emit('update:open', false)
}

function onKey(e: KeyboardEvent): void {
    if (e.key === 'Escape') close()
}

watch(() => props.open, (val) => {
    if (val) {
        document.addEventListener('keydown', onKey)
        nextTick(() => {
            const modal = document.querySelector('.modal') as HTMLElement | null
            modal?.focus()
        })
    } else {
        document.removeEventListener('keydown', onKey)
    }
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="modal-backdrop"
            @click.self="closeOnOverlay ? close() : undefined"
        >
            <div
                :class="['modal', `modal-${size}`]"
                tabindex="-1"
                role="dialog"
                :aria-modal="true"
                :aria-labelledby="title ? 'modal-title' : undefined"
            >
                <div class="modal-head">
                    <div>
                        <h2 v-if="title" id="modal-title">{{ title }}</h2>
                        <p v-if="description">{{ description }}</p>
                    </div>
                    <button
                        class="btn btn-ghost btn-icon btn-sm"
                        style="color:var(--text-muted);flex-shrink:0;"
                        aria-label="Cerrar"
                        @click="close"
                    >
                        <Icon name="x" :size="16" />
                    </button>
                </div>
                <div class="modal-body">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="modal-foot">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
