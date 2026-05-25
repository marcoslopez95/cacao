<script setup lang="ts">
import type { UserFormData, BenefitItem } from '@/types/userForm'
import { UF_BENEFITS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

function addBenefit(): void {
    const items = props.data.benefits ?? []
    props.setField('benefits', [...items, { __id: Date.now(), active: true }])
}

function removeBenefit(i: number): void {
    const items = [...(props.data.benefits ?? [])]
    items.splice(i, 1)
    props.setField('benefits', items)
}

function updateBenefit(i: number, patch: Partial<BenefitItem>): void {
    const items = (props.data.benefits ?? []).map((b, idx) =>
        idx === i ? { ...b, ...patch } : b,
    )
    props.setField('benefits', items)
}
</script>

<template>
    <AppRepeatable
        :items="data.benefits ?? []"
        add-label="Agregar beneficio"
        @add="addBenefit"
        @remove="removeBenefit"
    >
        <template #item="{ index }">
            <div class="uf-grid" style="padding: 14px;">
                <AppFormField label="Beneficio" required :col="6">
                    <select
                        class="uf-select"
                        :value="data.benefits?.[index]?.benefit ?? ''"
                        @change="updateBenefit(index, { benefit: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="b in UF_BENEFITS" :key="b" :value="b">{{ b }}</option>
                    </select>
                </AppFormField>

                <AppFormField :col="6" label=" ">
                    <AppToggle
                        :model-value="data.benefits?.[index]?.active ?? true"
                        label="Actualmente activo"
                        @update:model-value="updateBenefit(index, { active: $event })"
                    />
                </AppFormField>

                <AppFormField label="Fecha de inicio" optional :col="6">
                    <input
                        class="uf-input"
                        type="date"
                        :value="data.benefits?.[index]?.start"
                        @input="updateBenefit(index, { start: ($event.target as HTMLInputElement).value })"
                    />
                </AppFormField>

                <AppFormField label="Fecha de fin" optional :col="6" help="Dejá vacío si sigue activo">
                    <input
                        class="uf-input"
                        type="date"
                        :value="data.benefits?.[index]?.end"
                        @input="updateBenefit(index, { end: ($event.target as HTMLInputElement).value })"
                    />
                </AppFormField>
            </div>
        </template>
    </AppRepeatable>
</template>
