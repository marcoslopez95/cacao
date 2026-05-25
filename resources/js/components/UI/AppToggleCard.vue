<script setup lang="ts">
defineProps<{
    modelValue: boolean
    label: string
    sub?: string
}>()

defineEmits<{ 'update:modelValue': [boolean] }>()
</script>

<template>
    <label class="uf-toggle-card" :class="{ checked: modelValue }">
        <div class="uf-toggle-card-text">
            <strong>{{ label }}</strong>
            <span v-if="sub">{{ sub }}</span>
        </div>
        <span style="position: relative; flex-shrink: 0;">
            <input
                type="checkbox"
                :checked="modelValue"
                style="position: absolute; opacity: 0; inset: 0; cursor: pointer;"
                @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            />
            <span
                class="track"
                :style="{
                    position: 'relative',
                    display: 'inline-block',
                    width: '34px',
                    height: '20px',
                    borderRadius: '999px',
                    background: modelValue ? 'var(--accent)' : 'var(--border-strong)',
                    transition: 'background .15s',
                }"
            >
                <span
                    :style="{
                        position: 'absolute',
                        top: '2px',
                        left: modelValue ? '16px' : '2px',
                        width: '16px',
                        height: '16px',
                        background: '#fff',
                        borderRadius: '50%',
                        transition: 'left .15s',
                        boxShadow: '0 1px 2px rgba(0,0,0,0.2)',
                    }"
                />
            </span>
        </span>
    </label>
</template>
