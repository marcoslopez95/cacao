<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    typeId: number | null
    number: string
    catalog: { id: number; name: string; code: string }[]
}>()

const emit = defineEmits<{
    'update:typeId': [number | null]
    'update:number': [string]
}>()

const selectedCode = computed(() => props.catalog.find(t => t.id === props.typeId)?.code ?? null)

const placeholder = computed(() => {
    if (selectedCode.value === 'P') return 'AB1234567'
    if (selectedCode.value === 'J') return '123456789'
    return '12345678'
})

function onTypeChange(e: Event): void {
    const val = (e.target as HTMLSelectElement).value
    emit('update:typeId', val ? Number(val) : null)
}

function onNumber(e: Event): void {
    const raw = (e.target as HTMLInputElement).value
    const clean = selectedCode.value === 'P' ? raw.replace(/[^A-Za-z0-9]/g, '').toUpperCase() : raw.replace(/\D/g, '')
    emit('update:number', clean)
}
</script>

<template>
    <div class="uf-id-combo">
        <select
            class="uf-select"
            :value="typeId ?? ''"
            @change="onTypeChange"
        >
            <option :value="''">Seleccionar</option>
            <option v-for="t in catalog" :key="t.id" :value="t.id">{{ t.name }}</option>
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
