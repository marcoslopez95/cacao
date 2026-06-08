<script setup lang="ts">
import AppFormField from '@/components/UI/AppFormField.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import type { UserFormCatalogData } from '@/types/userEdit'
import type { UserFormData, AddressItem } from '@/types/userForm'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()

function statesForAddress(index: number) {
    const countryId = props.data.addresses?.[index]?.country_id

    if (!countryId) {
return []
}

    return props.catalogData.states.filter(s => s.country_id === countryId)
}

function addAddress(): void {
    const items = props.data.addresses ?? []
    const newItem: AddressItem = {
        __id: Date.now(),
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

function onCountryChange(index: number, value: string): void {
    const countryId = parseInt(value) || undefined
    updateAddress(index, { country_id: countryId, state_id: undefined })
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
                        :dusk="'address-country-select-' + index"
                        class="uf-select"
                        :value="data.addresses?.[index]?.country_id ?? ''"
                        @change="onCountryChange(index, ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="c in catalogData.countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Estado" :col="4">
                    <select
                        class="uf-select"
                        :value="data.addresses?.[index]?.state_id ?? ''"
                        @change="updateAddress(index, { state_id: parseInt(($event.target as HTMLSelectElement).value) || undefined })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="s in statesForAddress(index)" :key="s.id" :value="s.id">{{ s.name }}</option>
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
                        :dusk="'address-line1-' + index"
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
