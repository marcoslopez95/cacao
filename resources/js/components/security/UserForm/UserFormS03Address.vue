<script setup lang="ts">
import type { UserFormData, AddressItem } from '@/types/userForm'
import { UF_COUNTRIES, UF_STATES_VE, UF_MUNICIPIOS_DTTO, UF_PARROQUIAS, UF_ZONES } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

function addAddress(): void {
    const items = props.data.addresses ?? []
    const newItem: AddressItem = {
        __id: Date.now(),
        country: 've',
        primary: items.length === 0,
    }
    props.setField('addresses', [...items, newItem])
}

function removeAddress(i: number): void {
    const items = [...(props.data.addresses ?? [])]
    items.splice(i, 1)
    props.setField('addresses', items)
}

function updateAddress(i: number, patch: Partial<AddressItem>): void {
    const items = (props.data.addresses ?? []).map((a, idx) =>
        idx === i ? { ...a, ...patch } : a,
    )
    props.setField('addresses', items)
}

function setPrimary(i: number): void {
    const items = (props.data.addresses ?? []).map((a, idx) => ({
        ...a,
        primary: idx === i,
    }))
    props.setField('addresses', items)
}
</script>

<template>
    <AppRepeatable
        :items="data.addresses ?? []"
        add-label="Agregar otra dirección"
        :min="0"
        @add="addAddress"
        @remove="removeAddress"
    >
        <template #item="{ index }">
            <div class="uf-grid" style="padding: 14px;">
                <AppFormField label="País" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.country ?? ''"
                        @change="updateAddress(index, { country: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.key">{{ c.label }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Estado" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.state ?? ''"
                        @change="updateAddress(index, { state: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="s in UF_STATES_VE" :key="s" :value="s">{{ s }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Municipio" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.muni ?? ''"
                        @change="updateAddress(index, { muni: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="m in UF_MUNICIPIOS_DTTO" :key="m" :value="m">{{ m }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Parroquia" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.parish ?? ''"
                        @change="updateAddress(index, { parish: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="p in UF_PARROQUIAS" :key="p" :value="p">{{ p }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Zona" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.zone ?? ''"
                        @change="updateAddress(index, { zone: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="z in UF_ZONES" :key="z" :value="z">{{ z }}</option>
                    </select>
                </AppFormField>

                <AppFormField :col="4" label=" ">
                    <AppToggle
                        :model-value="data.addresses?.[index]?.primary ?? false"
                        label="Dirección principal"
                        @update:model-value="$event && setPrimary(index)"
                    />
                </AppFormField>

                <AppFormField label="Línea 1" required :col="12">
                    <input
                        class="uf-input"
                        :value="data.addresses?.[index]?.line1 ?? ''"
                        placeholder="Av. Principal, Casa/Apto…"
                        @input="updateAddress(index, { line1: ($event.target as HTMLInputElement).value })"
                    />
                </AppFormField>

                <AppFormField label="Línea 2" optional :col="12">
                    <input
                        class="uf-input"
                        :value="data.addresses?.[index]?.line2 ?? ''"
                        placeholder="Urbanización, sector, referencias…"
                        @input="updateAddress(index, { line2: ($event.target as HTMLInputElement).value })"
                    />
                </AppFormField>
            </div>
        </template>
    </AppRepeatable>
</template>
