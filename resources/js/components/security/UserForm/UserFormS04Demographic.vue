<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import type { UserFormCatalogData } from '@/types/userEdit'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Nacimiento</p>
        <div class="uf-grid">
            <AppFormField label="Ciudad de nacimiento" :col="4">
                <input
                    class="uf-input"
                    :value="data.birthCity"
                    placeholder="Caracas"
                    @input="setField('birthCity', ($event.target as HTMLInputElement).value)"
                />
            </AppFormField>
            <AppFormField label="País de nacimiento" :col="4">
                <select
                    class="uf-select"
                    :value="data.birthCountryId ?? ''"
                    @change="setField('birthCountryId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in catalogData.countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Estado de nacimiento" :col="4">
                <select
                    class="uf-select"
                    :value="data.birthStateId ?? ''"
                    @change="setField('birthStateId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option
                        v-for="s in catalogData.states.filter(s => !data.birthCountryId || s.country_id === data.birthCountryId)"
                        :key="s.id"
                        :value="s.id"
                    >{{ s.name }}</option>
                </select>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Identidad cultural</p>
        <div class="uf-grid">
            <AppFormField :col="12">
                <AppToggleCard
                    label="Pertenece a un pueblo indígena"
                    :model-value="data.indigenous ?? false"
                    @update:model-value="setField('indigenous', $event)"
                />
            </AppFormField>
            <template v-if="data.indigenous">
                <AppFormField label="Comunidad indígena" :col="6">
                    <input
                        class="uf-input"
                        :value="data.indigenousComm"
                        @input="setField('indigenousComm', ($event.target as HTMLInputElement).value)"
                    />
                </AppFormField>
                <AppFormField label="Lengua nativa" :col="6">
                    <select
                        class="uf-select"
                        :value="data.nativeLangId ?? ''"
                        @change="setField('nativeLangId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="l in catalogData.languages" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </AppFormField>
            </template>

            <AppFormField :col="12">
                <AppToggleCard
                    label="Es migrante retornado"
                    :model-value="data.returnedMigrant ?? false"
                    @update:model-value="setField('returnedMigrant', $event)"
                />
            </AppFormField>
            <template v-if="data.returnedMigrant">
                <AppFormField label="País de procedencia" :col="6">
                    <select
                        class="uf-select"
                        :value="data.previousCountryId ?? ''"
                        @change="setField('previousCountryId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="c in catalogData.countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </AppFormField>
            </template>

            <AppFormField label="Religión" optional :col="6">
                <select
                    class="uf-select"
                    :value="data.religionId ?? ''"
                    @change="setField('religionId', parseInt(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="r in catalogData.religions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </AppFormField>

            <AppFormField :col="12">
                <AppToggleCard
                    label="Practica algún deporte"
                    :model-value="data.sport ?? false"
                    @update:model-value="setField('sport', $event)"
                />
            </AppFormField>
            <template v-if="data.sport">
                <AppFormField label="Deporte" :col="6">
                    <input
                        class="uf-input"
                        :value="data.sportName"
                        @input="setField('sportName', ($event.target as HTMLInputElement).value)"
                    />
                </AppFormField>
            </template>

            <AppFormField label="Actividades culturales" optional :col="12">
                <textarea
                    class="uf-textarea"
                    :value="data.culture"
                    placeholder="Música, teatro, artesanías…"
                    @input="setField('culture', ($event.target as HTMLTextAreaElement).value)"
                />
            </AppFormField>
        </div>
    </div>
</template>
