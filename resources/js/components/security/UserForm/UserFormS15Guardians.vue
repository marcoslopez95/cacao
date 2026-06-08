<script setup lang="ts">

import AppFormField from '@/components/UI/AppFormField.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import type { UserFormData, GuardianItem } from '@/types/userForm'
import { UF_KINSHIP } from '@/types/userFormCatalogs'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

function addGuardian(): void {
    const items = props.data.guardians ?? []
    props.setField('guardians', [...items, { __id: Date.now() }])
}

function removeGuardian(i: number): void {
    const items = [...(props.data.guardians ?? [])]
    items.splice(i, 1)
    props.setField('guardians', items)
}

function updateGuardian(i: number, patch: Partial<GuardianItem>): void {
    const items = (props.data.guardians ?? []).map((g, idx) =>
        idx === i ? { ...g, ...patch } : g,
    )
    props.setField('guardians', items)
}

function setMain(i: number): void {
    const items = (props.data.guardians ?? []).map((g, idx) => ({
        ...g,
        main: idx === i,
    }))
    props.setField('guardians', items)
}

const SIMULATED_RESULT = { name: 'Carlos Ramírez', doc: 'V-10.234.567' }
</script>

<template>
    <AppRepeatable
        :items="data.guardians ?? []"
        add-label="Agregar representante"
        @add="addGuardian"
        @remove="removeGuardian"
    >
        <template #item="{ index }">
            <div style="padding: 14px; display: flex; flex-direction: column; gap: 12px;">
                <div class="uf-search-row">
                    <div class="uf-input-wrap has-left" style="flex: 1;">
                        <svg class="uf-ico-right" style="left:10px;right:auto;pointer-events:none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input
                            class="uf-input"
                            style="padding-left: 32px;"
                            :value="data.guardians?.[index]?.search ?? ''"
                            placeholder="Buscar por nombre o documento…"
                            @input="updateGuardian(index, { search: ($event.target as HTMLInputElement).value })"
                        />
                    </div>
                    <span class="uf-search-divider">o</span>
                    <button type="button" class="uf-btn secondary sm">Crear nuevo</button>
                </div>

                <div v-if="data.guardians?.[index]?.search" class="uf-search-result" style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--bg-surface-2);border:1px solid var(--border);border-radius:var(--r-md);">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-soft);display:grid;place-items:center;color:var(--accent);font-weight:600;font-size:13px;flex-shrink:0;">
                        {{ SIMULATED_RESULT.name[0] }}
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:500;">{{ SIMULATED_RESULT.name }}</div>
                        <div style="font-size:11.5px;color:var(--text-muted);">{{ SIMULATED_RESULT.doc }}</div>
                    </div>
                    <span style="font-family:var(--font-mono);font-size:10px;text-transform:uppercase;letter-spacing:.04em;padding:2px 8px;border-radius:var(--r-pill);background:var(--success-bg);color:var(--success-fg);">Vinculado</span>
                </div>

                <div class="uf-grid">
                    <AppFormField label="Parentesco" required :col="4">
                        <select
                            class="uf-select"
                            :value="data.guardians?.[index]?.kinship ?? ''"
                            @change="updateGuardian(index, { kinship: ($event.target as HTMLSelectElement).value })"
                        >
                            <option value="">Seleccionar</option>
                            <option v-for="k in UF_KINSHIP" :key="k" :value="k">{{ k }}</option>
                        </select>
                    </AppFormField>

                    <AppFormField :col="4" label=" ">
                        <AppToggle
                            :model-value="data.guardians?.[index]?.main ?? false"
                            label="Representante principal"
                            @update:model-value="$event && setMain(index)"
                        />
                    </AppFormField>

                    <AppFormField :col="4" label=" ">
                        <AppToggle
                            :model-value="data.guardians?.[index]?.emergency ?? false"
                            label="Contacto de emergencia"
                            @update:model-value="updateGuardian(index, { emergency: $event })"
                        />
                    </AppFormField>
                </div>
            </div>
        </template>
    </AppRepeatable>
</template>
