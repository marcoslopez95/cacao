<script setup lang="ts">
defineProps<{
    modelValue: boolean
    label?: string
    sub?: string
    disabled?: boolean
}>()

defineEmits<{ 'update:modelValue': [boolean] }>()
</script>

<template>
    <label class="uf-toggle">
        <input
            type="checkbox"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        />
        <span class="track" />
        <span v-if="label" class="lbl">
            {{ label }}
            <span v-if="sub" class="sub">{{ sub }}</span>
        </span>
    </label>
</template>
