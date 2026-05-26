<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Estado civil del representante" :col="6">
            <select
                class="uf-select"
                :value="data.repMaritalId ?? ''"
                @change="setField('repMaritalId', Number(($event.target as HTMLSelectElement).value) || undefined)"
            >
                <option value="">Seleccionar</option>
                <option v-for="m in catalogData.maritalStatuses" :key="m.id" :value="m.id">{{ m.name }}</option>
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
            <select
                class="uf-select"
                :value="data.livingId ?? ''"
                @change="setField('livingId', Number(($event.target as HTMLSelectElement).value) || undefined)"
            >
                <option value="">Seleccionar</option>
                <option v-for="l in catalogData.livingArrangements" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Tipo de jefe del hogar" :col="6">
            <select
                class="uf-select"
                :value="data.householdHeadId ?? ''"
                @change="setField('householdHeadId', Number(($event.target as HTMLSelectElement).value) || undefined)"
            >
                <option value="">Seleccionar</option>
                <option v-for="h in catalogData.householdHeadTypes" :key="h.id" :value="h.id">{{ h.name }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Nombre del jefe del hogar" :col="6">
            <input class="uf-input" :value="data.householdHeadName" @input="setField('householdHeadName', ($event.target as HTMLInputElement).value)" />
        </AppFormField>
    </div>
</template>
