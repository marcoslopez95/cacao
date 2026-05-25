<script setup lang="ts">
import { computed } from 'vue'
import type { UserFormData } from '@/types/userForm'
import { UF_DOC_TYPES, UF_GENDERS, UF_COUNTRIES } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppDocInput from '@/components/UI/AppDocInput.vue'
import AppTelInput from '@/components/UI/AppTelInput.vue'
import AppAvatarUpload from '@/components/UI/AppAvatarUpload.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

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
                    :type="data.docType ?? 'V-CI'"
                    :number="data.docNumber ?? ''"
                    @update:type="setField('docType', $event)"
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
                    :value="data.gender ?? ''"
                    @change="setField('gender', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="g in UF_GENDERS" :key="g.key" :value="g.key">{{ g.label }}</option>
                </select>
            </AppFormField>

            <AppFormField label="Nacionalidad" :col="4">
                <select
                    class="uf-select"
                    :value="data.nationality ?? ''"
                    @change="setField('nationality', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.key">{{ c.label }}</option>
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
