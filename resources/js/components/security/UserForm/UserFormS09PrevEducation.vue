<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_INSTITUTION_TYPES, UF_TRANSFER_REASONS, UF_DIGITAL_LEVELS, UF_EDU_LEVELS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Institución previa</p>
        <div class="uf-grid">
            <AppFormField label="Institución" :col="6">
                <input class="uf-input" :value="data.prevInstitution" @input="setField('prevInstitution', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Tipo" :col="3">
                <select class="uf-select" :value="data.prevInstitutionType ?? ''" @change="setField('prevInstitutionType', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="t in UF_INSTITUTION_TYPES" :key="t" :value="t">{{ t }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Año de egreso" :col="3">
                <input class="uf-input" type="number" min="1990" :max="new Date().getFullYear()" :value="data.gradYear" @input="setField('gradYear', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Promedio" :col="4">
                <div class="uf-input-wrap has-right">
                    <input class="uf-input" type="number" min="0" max="20" step="0.01" :value="data.prevGpa" placeholder="0.00" @input="setField('prevGpa', ($event.target as HTMLInputElement).value)" />
                    <span class="uf-ico-right" style="pointer-events:none;font-size:11px;color:var(--text-muted);">/ 20</span>
                </div>
            </AppFormField>
            <AppFormField label="Motivo de traslado" :col="4">
                <select class="uf-select" :value="data.transferReason ?? ''" @change="setField('transferReason', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="r in UF_TRANSFER_REASONS" :key="r" :value="r">{{ r }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Nivel digital" :col="4">
                <select class="uf-select" :value="data.digitalLevel ?? ''" @change="setField('digitalLevel', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="d in UF_DIGITAL_LEVELS" :key="d" :value="d">{{ d }}</option>
                </select>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Historia académica</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard label="Repitió algún grado" :model-value="data.repeated ?? false" @update:model-value="setField('repeated', $event)" />
            </AppFormField>
            <template v-if="data.repeated">
                <AppFormField label="Descripción" :col="12">
                    <input class="uf-input" :value="data.repeatedDesc" @input="setField('repeatedDesc', ($event.target as HTMLInputElement).value)" />
                </AppFormField>
            </template>

            <AppFormField :col="12">
                <AppToggleCard label="Tiene estudios universitarios previos" :model-value="data.priorUni ?? false" @update:model-value="setField('priorUni', $event)" />
            </AppFormField>
            <template v-if="data.priorUni">
                <AppFormField label="Detalle" :col="12">
                    <textarea class="uf-textarea" :value="data.priorUniDesc" @input="setField('priorUniDesc', ($event.target as HTMLTextAreaElement).value)" />
                </AppFormField>
            </template>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Educación del grupo familiar</p>
        <div class="uf-grid">
            <AppFormField label="Nivel educativo de la madre" :col="6">
                <select class="uf-select" :value="data.motherEdu ?? ''" @change="setField('motherEdu', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="e in UF_EDU_LEVELS" :key="e" :value="e">{{ e }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Nivel educativo del padre" :col="6">
                <select class="uf-select" :value="data.fatherEdu ?? ''" @change="setField('fatherEdu', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="e in UF_EDU_LEVELS" :key="e" :value="e">{{ e }}</option>
                </select>
            </AppFormField>
        </div>
    </div>
</template>
