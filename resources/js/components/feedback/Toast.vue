<script setup lang="ts">
import Icon from '@/components/base/Icon.vue'
import { useToast } from '@/components/feedback/useToast'

const { toasts, dismiss } = useToast()

const variantIcon: Record<string, string> = {
    success: 'check',
    warning: 'alert',
    danger:  'x',
    info:    'info',
    neutral: 'info',
}

const variantColor: Record<string, string> = {
    success: 'var(--success)',
    warning: 'var(--warning)',
    danger:  'var(--danger)',
    info:    'var(--info)',
    neutral: 'var(--text-muted)',
}
</script>

<template>
    <div class="toast-stack" aria-live="polite">
        <div
            v-for="t in toasts"
            :key="t.id"
            :class="['toast', t.leaving ? 'leaving' : '']"
            :role="t.variant === 'danger' ? 'alert' : 'status'"
        >
            <Icon
                :name="variantIcon[t.variant]"
                :size="16"
                :style="{ color: variantColor[t.variant], flexShrink: 0 }"
            />
            <span style="flex:1;font-size:var(--text-sm);">{{ t.message }}</span>
            <button
                v-if="t.action"
                class="btn btn-link btn-sm"
                style="flex-shrink:0;"
                @click="t.action!.onClick(); dismiss(t.id)"
            >
                {{ t.action.label }}
            </button>
            <button
                class="btn btn-ghost btn-icon btn-sm"
                style="flex-shrink:0;color:var(--text-muted);"
                :aria-label="'Cerrar notificación'"
                @click="dismiss(t.id)"
            >
                <Icon name="x" :size="14" />
            </button>
        </div>
    </div>
</template>
