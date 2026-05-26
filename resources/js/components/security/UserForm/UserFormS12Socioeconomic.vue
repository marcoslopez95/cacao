<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Ingresos del hogar</p>
        <div class="uf-grid">
            <AppFormField label="Rango de ingreso mensual" :col="6">
                <select
                    class="uf-select"
                    :value="data.incomeRangeId ?? ''"
                    @change="setField('incomeRangeId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="r in catalogData.incomeRanges" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Fuente principal de ingreso" :col="6">
                <select
                    class="uf-select"
                    :value="data.incomeSourceId ?? ''"
                    @change="setField('incomeSourceId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="s in catalogData.incomeSources" :key="s.id" :value="s.id">{{ s.name }}</option>
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
                    <select
                        class="uf-select"
                        :value="data.remitFromId ?? ''"
                        @change="setField('remitFromId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="c in catalogData.countries" :key="c.id" :value="c.id">{{ c.name }}</option>
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
                    <select
                        class="uf-select"
                        :value="data.employmentTypeId ?? ''"
                        @change="setField('employmentTypeId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="t in catalogData.employmentTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
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
