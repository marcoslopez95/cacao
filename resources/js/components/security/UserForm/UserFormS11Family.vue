<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_MARITAL, UF_LIVING, UF_HOUSEHOLD_HEAD } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Estado civil del representante" :col="6">
            <select class="uf-select" :value="data.repMarital ?? ''" @change="setField('repMarital', ($event.target as HTMLSelectElement).value)">
                <option value="">Seleccionar</option>
                <option v-for="m in UF_MARITAL" :key="m" :value="m">{{ m }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Número de hijos" :col="3">
            <input class="uf-input" type="number" min="0" :value="data.repChildren" @input="setField('repChildren', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Número de hermanos" :col="3">
            <input class="uf-input" type="number" min="0" :value="data.siblings" @input="setField('siblings', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Posición entre hermanos" :col="4">
            <input class="uf-input" type="number" min="1" :value="data.siblingPos" @input="setField('siblingPos', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Arreglo de convivencia" :col="8">
            <select class="uf-select" :value="data.living ?? ''" @change="setField('living', ($event.target as HTMLSelectElement).value)">
                <option value="">Seleccionar</option>
                <option v-for="l in UF_LIVING" :key="l" :value="l">{{ l }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Tipo de jefe del hogar" :col="6">
            <select class="uf-select" :value="data.householdHead ?? ''" @change="setField('householdHead', ($event.target as HTMLSelectElement).value)">
                <option value="">Seleccionar</option>
                <option v-for="h in UF_HOUSEHOLD_HEAD" :key="h" :value="h">{{ h }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Nombre del jefe del hogar" :col="6">
            <input class="uf-input" :value="data.householdHeadName" @input="setField('householdHeadName', ($event.target as HTMLInputElement).value)" />
        </AppFormField>
    </div>
</template>
