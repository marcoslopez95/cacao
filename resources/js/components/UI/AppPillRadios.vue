<script setup lang="ts">
defineProps<{
    modelValue: string
    options: string[] | Array<{ key: string; label: string }>
}>()

defineEmits<{ 'update:modelValue': [string] }>()

function optKey(o: string | { key: string; label: string }): string {
    return typeof o === 'string' ? o : o.key
}

function optLabel(o: string | { key: string; label: string }): string {
    return typeof o === 'string' ? o : o.label
}
</script>

<template>
    <div class="uf-pill-radios">
        <label
            v-for="o in options"
            :key="optKey(o)"
            class="uf-pill-radio"
            :class="{ checked: modelValue === optKey(o) }"
        >
            <input
                type="radio"
                :checked="modelValue === optKey(o)"
                @change="$emit('update:modelValue', optKey(o))"
            />
            {{ optLabel(o) }}
        </label>
    </div>
</template>
