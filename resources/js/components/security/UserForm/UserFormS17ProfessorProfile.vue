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
        <p class="uf-sub-title">Datos laborales</p>
        <div class="uf-grid">
            <AppFormField label="Código de empleado" admin-only :col="4">
                <input class="uf-input" :value="data.empCode" @input="setField('empCode', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Título académico" :col="4">
                <input dusk="degree-input" class="uf-input" :value="data.degree" placeholder="Lic. en…" @input="setField('degree', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Especialidad" :col="4">
                <input class="uf-input" :value="data.specialty" @input="setField('specialty', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Contrato y dedicación</p>
        <div class="uf-grid">
            <AppFormField label="Tipo de contrato" :col="4">
                <select
                    dusk="contract-type-select"
                    class="uf-select"
                    :value="data.contractTypeId ?? ''"
                    @change="setField('contractTypeId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in catalogData.contractTypes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Dedicación" :col="4">
                <select
                    dusk="dedication-type-select"
                    class="uf-select"
                    :value="data.dedicationTypeId ?? ''"
                    @change="setField('dedicationTypeId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="d in catalogData.dedicationTypes" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Carga horaria" :col="4">
                <div class="uf-input-wrap has-right">
                    <input class="uf-input" type="number" min="0" :value="data.weeklyHours" @input="setField('weeklyHours', ($event.target as HTMLInputElement).value)" />
                    <span class="uf-ico-right" style="pointer-events:none;font-size:11px;color:var(--text-muted);">hs</span>
                </div>
            </AppFormField>
            <AppFormField label="Fecha de ingreso" required :col="4">
                <input dusk="hire-date-input" class="uf-input" type="date" :value="data.hireDate" @input="setField('hireDate', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Fecha de egreso" optional :col="4">
                <input class="uf-input" type="date" :value="data.endDate" @input="setField('endDate', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Estatus laboral" admin-only :col="4">
                <select
                    dusk="employment-status-select"
                    class="uf-select"
                    :value="data.emplStatusId ?? ''"
                    @change="setField('emplStatusId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="s in catalogData.employmentStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Coordinación</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard
                    label="Es coordinador de departamento"
                    :model-value="data.isCoord ?? false"
                    @update:model-value="setField('isCoord', $event)"
                />
            </AppFormField>
            <template v-if="data.isCoord">
                <AppFormField label="Departamento" :col="8">
                    <select
                        class="uf-select"
                        :value="data.coordDeptId ?? ''"
                        @change="setField('coordDeptId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="d in catalogData.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </AppFormField>
                <AppFormField label="Coordinador desde" :col="4">
                    <input class="uf-input" type="date" :value="data.coordSince" @input="setField('coordSince', ($event.target as HTMLInputElement).value)" />
                </AppFormField>
            </template>
        </div>
    </div>
</template>
