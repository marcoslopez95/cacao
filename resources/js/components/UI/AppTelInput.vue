<script setup lang="ts">
import { UF_COUNTRIES } from '@/types/userFormCatalogs'

defineProps<{
    dial: string
    number: string
}>()

const emit = defineEmits<{
    'update:dial': [string]
    'update:number': [string]
}>()

function onNumber(e: Event): void {
    const clean = (e.target as HTMLInputElement).value.replace(/\D/g, '')
    emit('update:number', clean)
}
</script>

<template>
    <div class="uf-tel-combo">
        <select
            class="uf-select"
            :value="dial"
            @change="$emit('update:dial', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.dial">
                {{ c.label }} {{ c.dial }}
            </option>
        </select>
        <input
            class="uf-input"
            type="tel"
            inputmode="numeric"
            :value="number"
            placeholder="4121234567"
            @input="onNumber"
        />
    </div>
</template>
