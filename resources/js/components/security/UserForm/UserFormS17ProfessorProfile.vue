<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_CONTRACT, UF_DEDICATION, UF_EMPL_STATUS, UF_DEPARTMENTS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
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
                <input class="uf-input" :value="data.degree" placeholder="Lic. en…" @input="setField('degree', ($event.target as HTMLInputElement).value)" />
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
                <select class="uf-select" :value="data.contract ?? ''" @change="setField('contract', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="c in UF_CONTRACT" :key="c" :value="c">{{ c }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Dedicación" :col="4">
                <select class="uf-select" :value="data.dedication ?? ''" @change="setField('dedication', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="d in UF_DEDICATION" :key="d" :value="d">{{ d }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Carga horaria" :col="4">
                <div class="uf-input-wrap has-right">
                    <input class="uf-input" type="number" min="0" :value="data.weeklyHours" @input="setField('weeklyHours', ($event.target as HTMLInputElement).value)" />
                    <span class="uf-ico-right" style="pointer-events:none;font-size:11px;color:var(--text-muted);">hs</span>
                </div>
            </AppFormField>
            <AppFormField label="Fecha de ingreso" required :col="4">
                <input class="uf-input" type="date" :value="data.hireDate" @input="setField('hireDate', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Fecha de egreso" optional :col="4">
                <input class="uf-input" type="date" :value="data.endDate" @input="setField('endDate', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Estatus laboral" admin-only :col="4">
                <select class="uf-select" :value="data.emplStatus ?? ''" @change="setField('emplStatus', ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar</option>
                    <option v-for="s in UF_EMPL_STATUS" :key="s" :value="s">{{ s }}</option>
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
                    <select class="uf-select" :value="data.coordDept ?? ''" @change="setField('coordDept', ($event.target as HTMLSelectElement).value)">
                        <option value="">Seleccionar</option>
                        <option v-for="d in UF_DEPARTMENTS" :key="d" :value="d">{{ d }}</option>
                    </select>
                </AppFormField>
                <AppFormField label="Coordinador desde" :col="4">
                    <input class="uf-input" type="date" :value="data.coordSince" @input="setField('coordSince', ($event.target as HTMLInputElement).value)" />
                </AppFormField>
            </template>
        </div>
    </div>
</template>
