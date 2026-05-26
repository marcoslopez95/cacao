<script setup lang="ts">
import { computed } from 'vue'
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppDocInput from '@/components/UI/AppDocInput.vue'
import AppTelInput from '@/components/UI/AppTelInput.vue'
import AppAvatarUpload from '@/components/UI/AppAvatarUpload.vue'

const props = withDefaults(defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData?: UserFormCatalogData
}>(), {
    catalogData: () => ({
        documentTypes: [],
        genders: [],
        nationalities: [],
        countries: [],
        states: [],
        languages: [],
        languageLevels: [],
        benefits: [],
        religions: [],
        institutionTypes: [],
        transferReasons: [],
        digitalLevels: [],
        educationLevels: [],
        maritalStatuses: [],
        contractTypes: [],
        dedicationTypes: [],
        employmentStatuses: [],
        departments: [],
        bloodTypes: [],
        disabilityTypes: [],
        insuranceTypes: [],
        livingArrangements: [],
        householdHeadTypes: [],
        incomeRanges: [],
        incomeSources: [],
        employmentTypes: [],
        housingTypes: [],
        tenureTypes: [],
        constructionMaterials: [],
        commuteTimes: [],
        transportTypes: [],
    }),
})

const fullName = computed(() =>
    [props.data.firstName, props.data.lastName].filter(Boolean).join(' '),
)
</script>

<template>
    <div class="uf-section-body-inner">
        <AppAvatarUpload
            :src="data.profilePhotoUrl"
            :name="fullName"
            @upload="() => {}"
            @remove="setField('profilePhotoUrl', undefined)"
        />

        <div class="uf-grid" style="margin-top: 20px;">
            <AppFormField label="Nombres" required :col="6">
                <input
                    class="uf-input"
                    :value="data.firstName"
                    placeholder="María Elena"
                    @input="setField('firstName', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>

            <AppFormField label="Apellidos" required :col="6">
                <input
                    class="uf-input"
                    :value="data.lastName"
                    placeholder="González Pérez"
                    @input="setField('lastName', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>

            <AppFormField label="Documento de identidad" required :col="6">
                <AppDocInput
                    :type-id="data.docTypeId ?? null"
                    :number="data.docNumber ?? ''"
                    :catalog="catalogData.documentTypes"
                    @update:type-id="setField('docTypeId', $event)"
                    @update:number="setField('docNumber', $event)"
                />
            </AppFormField>

            <AppFormField label="Fecha de nacimiento" required :col="3">
                <input
                    class="uf-input"
                    type="date"
                    :value="data.birthDate"
                    @input="setField('birthDate', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>

            <AppFormField label="Género" :col="3">
                <select
                    class="uf-select"
                    :value="data.genderId ?? null"
                    @change="setField('genderId', Number(($event.target as HTMLSelectElement).value) || null)"
                >
                    <option :value="null">Seleccionar</option>
                    <option v-for="g in catalogData.genders" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
            </AppFormField>

            <AppFormField label="Nacionalidad" :col="4">
                <select
                    class="uf-select"
                    :value="data.nationalityId ?? null"
                    @change="setField('nationalityId', Number(($event.target as HTMLSelectElement).value) || null)"
                >
                    <option :value="null">Seleccionar</option>
                    <option v-for="c in catalogData.nationalities" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </AppFormField>

            <AppFormField label="Teléfono principal" required :col="4">
                <AppTelInput
                    :dial="data.phone1Dial ?? '+58'"
                    :number="data.phone1 ?? ''"
                    @update:dial="setField('phone1Dial', $event)"
                    @update:number="setField('phone1', $event)"
                />
            </AppFormField>

            <AppFormField label="Teléfono secundario" optional :col="4">
                <AppTelInput
                    :dial="data.phone2Dial ?? '+58'"
                    :number="data.phone2 ?? ''"
                    @update:dial="setField('phone2Dial', $event)"
                    @update:number="setField('phone2', $event)"
                />
            </AppFormField>
        </div>
    </div>
</template>
