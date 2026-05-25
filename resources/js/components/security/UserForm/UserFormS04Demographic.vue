<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_COUNTRIES, UF_STATES_VE, UF_LANGUAGES, UF_RELIGIONS } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppToggleCard from '@/components/UI/AppToggleCard.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
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
            <AppFormField label="Estado" :col="4">
                <select
                    class="uf-select"
                    :value="data.birthState ?? ''"
                    @change="setField('birthState', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="s in UF_STATES_VE" :key="s" :value="s">{{ s }}</option>
                </select>
            </AppFormField>
            <AppFormField label="País de nacimiento" :col="4">
                <select
                    class="uf-select"
                    :value="data.birthCountry ?? ''"
                    @change="setField('birthCountry', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.key">{{ c.label }}</option>
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
                        :value="data.nativeLang ?? ''"
                        @change="setField('nativeLang', ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="l in UF_LANGUAGES" :key="l" :value="l">{{ l }}</option>
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
                        :value="data.returnFrom ?? ''"
                        @change="setField('returnFrom', ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">Seleccionar</option>
                        <option v-for="c in UF_COUNTRIES" :key="c.key" :value="c.key">{{ c.label }}</option>
                    </select>
                </AppFormField>
            </template>

            <AppFormField label="Religión" optional :col="6">
                <select
                    class="uf-select"
                    :value="data.religion ?? ''"
                    @change="setField('religion', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="r in UF_RELIGIONS" :key="r" :value="r">{{ r }}</option>
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
