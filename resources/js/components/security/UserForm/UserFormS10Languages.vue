<script setup lang="ts">
import type { UserFormData, LanguageItem } from '@/types/userForm'
import { UF_LANGUAGES, UF_LANG_LEVELS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggle from '@/components/UI/AppToggle.vue'
import AppRepeatable from '@/components/UI/AppRepeatable.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

function addLanguage(): void {
    const items = props.data.languages ?? []
    props.setField('languages', [...items, { __id: Date.now() }])
}

function removeLanguage(i: number): void {
    const items = [...(props.data.languages ?? [])]
    items.splice(i, 1)
    props.setField('languages', items)
}

function updateLanguage(i: number, patch: Partial<LanguageItem>): void {
    const items = (props.data.languages ?? []).map((l, idx) =>
        idx === i ? { ...l, ...patch } : l,
    )
    props.setField('languages', items)
}
</script>

<template>
    <AppRepeatable
        :items="data.languages ?? []"
        add-label="Agregar idioma"
        @add="addLanguage"
        @remove="removeLanguage"
    >
        <template #item="{ index }">
            <div class="uf-grid" style="padding: 14px;">
                <AppFormField label="Idioma" required :col="5">
                    <select
                        class="uf-select"
                        :value="data.languages?.[index]?.lang ?? ''"
                        @change="updateLanguage(index, { lang: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="l in UF_LANGUAGES" :key="l" :value="l">{{ l }}</option>
                    </select>
                </AppFormField>

                <AppFormField label="Nivel" required :col="5">
                    <select
                        class="uf-select"
                        :value="data.languages?.[index]?.level ?? ''"
                        @change="updateLanguage(index, { level: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="lv in UF_LANG_LEVELS" :key="lv" :value="lv">{{ lv }}</option>
                    </select>
                </AppFormField>

                <AppFormField :col="2" label=" ">
                    <AppToggle
                        :model-value="data.languages?.[index]?.mother ?? false"
                        label="Materna"
                        @update:model-value="updateLanguage(index, { mother: $event })"
                    />
                </AppFormField>
            </div>
        </template>
    </AppRepeatable>
</template>
