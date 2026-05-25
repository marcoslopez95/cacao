<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_INCOME_RANGES, UF_INCOME_SOURCES, UF_EMPLOYMENT_TYPES, UF_COUNTRIES } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Ingresos del hogar</p>
        <div class="uf-grid">
            <AppFormField label="Rango de ingreso mensual" :col="6">
                <select class="uf-select" :value="data.incomeRange ?? ''" @change="setField('incomeRange', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="r in UF_INCOME_RANGES" :key="r" :value="r">{{ r }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Fuente principal de ingreso" :col="6">
                <select class="uf-select" :value="data.incomeSource ?? ''" @change="setField('incomeSource', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="s in UF_INCOME_SOURCES" :key="s" :value="s">{{ s }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Personas que aportan ingresos" :col="6">
                <input class="uf-input" type="number" min="0" :value="data.contributors" @input="setField('contributors', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Fecha estudio socioeconómico" admin-only :col="6">
                <input class="uf-input" type="date" :value="data.socioDate" @input="setField('socioDate', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField :col="12">
                <AppToggleCard label="Recibe remesas del exterior" :model-value="data.remit ?? false" @update:model-value="setField('remit', $event)" />
            </AppFormField>
            <template v-if="data.remit">
                <AppFormField label="País de origen de las remesas" :col="6">
                    <select class="uf-select" :value="data.remitFrom ?? ''" @change="setField('remitFrom', ($event.target as HTMLSelectElement).value)">
                        <option value="">Seleccionar</option>
                        <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.key">{{ c.label }}</option>
                    </select>
                </AppFormField>
            </template>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Empleo y becas del estudiante</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard label="El estudiante trabaja actualmente" :model-value="data.studentWorks ?? false" @update:model-value="setField('studentWorks', $event)" />
            </AppFormField>
            <template v-if="data.studentWorks">
                <AppFormField label="Tipo de empleo" :col="6">
                    <select class="uf-select" :value="data.employmentType ?? ''" @change="setField('employmentType', ($event.target as HTMLSelectElement).value)">
                        <option value="">Seleccionar</option>
                        <option v-for="t in UF_EMPLOYMENT_TYPES" :key="t" :value="t">{{ t }}</option>
                    </select>
                </AppFormField>
                <AppFormField label="Horas semanales" :col="6">
                    <div class="uf-input-wrap has-right">
                        <input class="uf-input" type="number" min="0" :value="data.weekHours" @input="setField('weekHours', ($event.target as HTMLInputElement).value)" />
                        <span class="uf-ico-right" style="pointer-events:none;font-size:11px;color:var(--text-muted);">hs</span>
                    </div>
                </AppFormField>
            </template>

            <AppFormField :col="12">
                <AppToggleCard label="Tiene beca externa" :model-value="data.externalScholarship ?? false" @update:model-value="setField('externalScholarship', $event)" />
            </AppFormField>
            <template v-if="data.externalScholarship">
                <AppFormField label="Nombre de la beca" :col="6">
                    <input class="uf-input" :value="data.scholarshipName" @input="setField('scholarshipName', ($event.target as HTMLInputElement).value)" />
                </AppFormField>
            </template>

            <AppFormField :col="12">
                <AppToggleCard
                    label="Recibe beneficios institucionales"
                    sub="Ver sección Beneficios para el detalle"
                    :model-value="data.instBenefits ?? false"
                    @update:model-value="setField('instBenefits', $event)"
                />
            </AppFormField>
        </div>
    </div>
</template>
