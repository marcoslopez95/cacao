<script setup lang="ts">
import AppFileZone from '@/components/UI/AppFileZone.vue'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import type { UserFormData, AttachmentItem } from '@/types/userForm'
import { UF_ATTACH_TYPES } from '@/types/userFormCatalogs'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

function addAttachment(): void {
    const items = props.data.attachments ?? []
    props.setField('attachments', [...items, { __id: Date.now() }])
}

function removeAttachment(i: number): void {
    const items = [...(props.data.attachments ?? [])]
    items.splice(i, 1)
    props.setField('attachments', items)
}

function updateAttachment(i: number, patch: Partial<AttachmentItem>): void {
    const items = (props.data.attachments ?? []).map((a, idx) =>
        idx === i ? { ...a, ...patch } : a,
    )
    props.setField('attachments', items)
}
</script>

<template>
    <AppRepeatable
        :items="data.attachments ?? []"
        add-label="Agregar documento"
        @add="addAttachment"
        @remove="removeAttachment"
    >
        <template #item="{ index }">
            <div class="uf-grid" style="padding: 14px;">
                <AppFormField label="Tipo de documento" required :col="5">
                    <select
                        class="uf-select"
                        :value="data.attachments?.[index]?.type ?? ''"
                        @change="updateAttachment(index, { type: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="t in UF_ATTACH_TYPES" :key="t" :value="t">{{ t }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Archivo" required :col="7">
                    <AppFileZone
                        :label="data.attachments?.[index]?.filename ?? undefined"
                        hint="PDF, JPG, PNG · máx. 5 MB"
                        @pick="updateAttachment(index, { filename: $event })"
                    />
                </AppFormField>

                <AppFormField :col="6" admin-only>
                    <AppToggle
                        :model-value="data.attachments?.[index]?.verified ?? false"
                        label="Documento verificado"
                        @update:model-value="updateAttachment(index, {
                            verified: $event,
                            verifiedAt: $event ? new Date().toISOString() : null,
                        })"
                    />
                </AppFormField>

                <AppFormField v-if="data.attachments?.[index]?.verified" :col="6">
                    <span class="uf-ok">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Documento aceptado
                    </span>
                </AppFormField>
            </div>
        </template>
    </AppRepeatable>
</template>
