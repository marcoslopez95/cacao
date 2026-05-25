<script setup lang="ts">
import { computed } from 'vue'
import { UF_DOC_TYPES } from '@/types/userFormCatalogs'

const props = defineProps<{
    type: string
    number: string
}>()

const emit = defineEmits<{
    'update:type': [string]
    'update:number': [string]
}>()

const placeholder = computed(() => {
    if (props.type === 'PAS') return 'AB123456'
    if (props.type === 'J-CI') return '123456789'
    return '12345678'
})

function onNumber(e: Event): void {
    const raw = (e.target as HTMLInputElement).value
    const clean = props.type === 'PAS' ? raw.replace(/[^A-Za-z0-9]/g, '').toUpperCase() : raw.replace(/\D/g, '')
    emit('update:number', clean)
}
</script>

<template>
    <div class="uf-id-combo">
        <select
            class="uf-select"
            :value="type"
            @change="$emit('update:type', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="t in UF_DOC_TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
        </select>
        <input
            class="uf-input"
            type="text"
            inputmode="numeric"
            :value="number"
            :placeholder="placeholder"
            @input="onNumber"
        />
    </div>
</template>
