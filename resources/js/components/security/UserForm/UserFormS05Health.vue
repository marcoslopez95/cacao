<script setup lang="ts">
import { computed } from 'vue'
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'
import AppTelInput from '@/components/UI/AppTelInput.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()

const imc = computed(() => {
    const w = parseFloat(props.data.weight ?? '')
    const h = parseFloat(props.data.height ?? '') / 100
    if (!w || !h) return null
    return (w / (h * h)).toFixed(1)
})
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Generalidades</p>
        <div class="uf-grid">
            <AppFormField label="Grupo sanguíneo" :col="3">
                <select
                    dusk="blood-type-select"
                    class="uf-select"
                    :value="data.bloodTypeId ?? ''"
                    @change="setField('bloodTypeId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="item in catalogData.bloodTypes" :key="item.id" :value="item.id">{{ item.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Peso (kg)" :col="3">
                <input
                    dusk="weight-input"
                    class="uf-input"
                    type="number"
                    min="0"
                    :value="data.weight"
                    placeholder="70"
                    @input="setField('weight', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>
            <AppFormField label="Talla (cm)" :col="3">
                <input
                    dusk="height-input"
                    class="uf-input"
                    type="number"
                    min="0"
                    :value="data.height"
                    placeholder="170"
                    @input="setField('height', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>
            <AppFormField label="IMC calculado" :col="3">
                <div v-if="imc" class="uf-calc ok">
                    <strong>{{ imc }}</strong> kg/m²
                </div>
                <div v-else class="uf-input" style="pointer-events:none;color:var(--text-muted);font-style:italic;">
                    Calculado automáticamente
                </div>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Condiciones especiales</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard
                    dusk="disability-toggle"
                    label="Tiene alguna discapacidad"
                    :model-value="data.disability ?? false"
                    @update:model-value="setField('disability', $event)"
                />
            </AppFormField>
            <template v-if="data.disability">
                <AppFormField label="Tipo de discapacidad" :col="6">
                    <select
                        dusk="disability-type-select"
                        class="uf-select"
                        :value="data.disabilityTypeId ?? ''"
                        @change="setField('disabilityTypeId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogData.disabilityTypes" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </AppFormField>
                <AppFormField label="Descripción" optional :col="6">
                    <textarea
                        class="uf-textarea"
                        :value="data.disabilityDesc"
                        @input="setField('disabilityDesc', ($event.target as HTMLTextAreaElement).value)"
                    />
                </AppFormField>
            </template>

            <AppFormField :col="12">
                <AppToggleCard
                    label="Tiene necesidades especiales"
                    :model-value="data.specialNeeds ?? false"
                    @update:model-value="setField('specialNeeds', $event)"
                />
            </AppFormField>
            <template v-if="data.specialNeeds">
                <AppFormField label="Descripción" :col="12">
                    <textarea
                        class="uf-textarea"
                        :value="data.specialNeedsDesc"
                        @input="setField('specialNeedsDesc', ($event.target as HTMLTextAreaElement).value)"
                    />
                </AppFormField>
            </template>

            <AppFormField label="Condición crónica" optional :col="4">
                <input class="uf-input" :value="data.chronic" @input="setField('chronic', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Medicación regular" optional :col="4">
                <input class="uf-input" :value="data.medication" @input="setField('medication', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Alergias" optional :col="4">
                <input class="uf-input" :value="data.allergies" @input="setField('allergies', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Seguro médico</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard
                    dusk="insurance-toggle"
                    label="Cuenta con seguro médico"
                    :model-value="data.insurance ?? false"
                    @update:model-value="setField('insurance', $event)"
                />
            </AppFormField>
            <template v-if="data.insurance">
                <AppFormField label="Tipo de seguro" :col="6">
                    <select
                        dusk="insurance-type-select"
                        class="uf-select"
                        :value="data.insuranceTypeId ?? ''"
                        @change="setField('insuranceTypeId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogData.insuranceTypes" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </AppFormField>
            </template>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Contacto de emergencia</p>
        <div class="uf-grid">
            <AppFormField label="Nombre" required :col="5">
                <input dusk="emergency-name-input" class="uf-input" :value="data.emergencyName" @input="setField('emergencyName', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Teléfono" required :col="4">
                <AppTelInput
                    :dial="data.emergencyDial ?? '+58'"
                    :number="data.emergencyPhone ?? ''"
                    @update:dial="setField('emergencyDial', $event)"
                    @update:number="setField('emergencyPhone', $event)"
                />
            </AppFormField>
            <AppFormField label="Parentesco" required :col="3">
                <input dusk="emergency-rel-input" class="uf-input" :value="data.emergencyRel" placeholder="Madre, padre…" @input="setField('emergencyRel', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
        </div>
    </div>
</template>
