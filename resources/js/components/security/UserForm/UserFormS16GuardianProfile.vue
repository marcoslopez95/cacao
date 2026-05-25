<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_MARITAL, UF_EDU_LEVELS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppTelInput from '@/components/UI/AppTelInput.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Ocupación" :col="6">
            <input class="uf-input" :value="data.occupation" @input="setField('occupation', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Empleador" :col="6">
            <input class="uf-input" :value="data.employer" @input="setField('employer', ($event.target as HTMLInputElement).value)" />
        </AppFormField>

        <AppFormField label="Teléfono del trabajo" optional :col="6">
            <AppTelInput
                :dial="data.workDial ?? '+58'"
                :number="data.workPhone ?? ''"
                @update:dial="setField('workDial', $event)"
                @update:number="setField('workPhone', $event)"
            />
        </AppFormField>

        <AppFormField label="Estado civil" :col="6">
            <select class="uf-select" :value="data.guardianMarital ?? ''" @change="setField('guardianMarital', ($event.target as HTMLSelectElement).value)">
                <option value="">Seleccionar</option>
                <option v-for="m in UF_MARITAL" :key="m" :value="m">{{ m }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Nivel educativo" :col="12">
            <select class="uf-select" :value="data.guardianEdu ?? ''" @change="setField('guardianEdu', ($event.target as HTMLSelectElement).value)">
                <option value="">Seleccionar</option>
                <option v-for="e in UF_EDU_LEVELS" :key="e" :value="e">{{ e }}</option>
            </select>
        </AppFormField>
    </div>
</template>
