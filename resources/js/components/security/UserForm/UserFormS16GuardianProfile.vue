<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppTelInput from '@/components/UI/AppTelInput.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Ocupación" :col="6">
            <input dusk="occupation-input" class="uf-input" :value="data.occupation" @input="setField('occupation', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Empleador" :col="6">
            <input dusk="employer-input" class="uf-input" :value="data.employer" @input="setField('employer', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Teléfono del trabajo" optional :col="6">
            <AppTelInput
                dusk-prefix="work-phone"
                :dial="data.workDial ?? '+58'"
                :number="data.workPhone ?? ''"
                @update:dial="setField('workDial', $event)"
                @update:number="setField('workPhone', $event)"
            />
        </AppFormField>

        <AppFormField label="Estado civil" :col="6">
            <select
                dusk="guardian-marital-select"
                class="uf-select"
                :value="data.guardianMaritalId ?? ''"
                @change="setField('guardianMaritalId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
            >
                <option value="">Seleccionar</option>
                <option v-for="m in catalogData.maritalStatuses" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Nivel educativo" :col="12">
            <select
                dusk="guardian-edu-select"
                class="uf-select"
                :value="data.guardianEduId ?? ''"
                @change="setField('guardianEduId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
            >
                <option value="">Seleccionar</option>
                <option v-for="e in catalogData.educationLevels" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
        </AppFormField>
    </div>
</template>
