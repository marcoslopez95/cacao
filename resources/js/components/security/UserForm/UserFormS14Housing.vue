<script setup lang="ts">
import { computed } from 'vue'
import AppFormField from '@/components/UI/AppFormField.vue'
import type { UserFormCatalogData } from '@/types/userEdit'
import type { UserFormData } from '@/types/userForm'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
    catalogData: UserFormCatalogData
}>()

const ratio = computed(() => {
    const people = parseFloat(props.data.peopleHome ?? '')
    const rooms = parseFloat(props.data.rooms ?? '')

    if (!people || !rooms) {
return null
}

    return (people / rooms).toFixed(1)
})

const SERVICES: Array<{ key: keyof UserFormData; label: string }> = [
    { key: 'svc_water', label: 'Agua potable' },
    { key: 'svc_elec', label: 'Electricidad' },
    { key: 'svc_gas', label: 'Gas doméstico' },
    { key: 'svc_inet', label: 'Internet' },
]
</script>

<template>
    <div class="uf-subsection">
        <p class="uf-sub-title">Tipo y tenencia</p>
        <div class="uf-grid">
            <AppFormField label="Tipo de vivienda" :col="4">
                <select
                    class="uf-select"
                    :value="data.housingId ?? ''"
                    @change="setField('housingId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="h in catalogData.housingTypes" :key="h.id" :value="h.id">{{ h.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Tenencia" :col="4">
                <select
                    class="uf-select"
                    :value="data.tenureId ?? ''"
                    @change="setField('tenureId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="t in catalogData.tenureTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Tipo de construcción" :col="4">
                <select
                    class="uf-select"
                    :value="data.constructionId ?? ''"
                    @change="setField('constructionId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in catalogData.constructionMaterials" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Composición del hogar</p>
        <div class="uf-grid">
            <AppFormField label="Habitaciones" :col="4">
                <input class="uf-input" type="number" min="0" :value="data.rooms" @input="setField('rooms', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Baños" :col="4">
                <input class="uf-input" type="number" min="0" :value="data.bathrooms" @input="setField('bathrooms', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <AppFormField label="Personas en el hogar" :col="4">
                <input class="uf-input" type="number" min="1" :value="data.peopleHome" @input="setField('peopleHome', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
            <div v-if="ratio" class="uf-field col-12">
                <div class="uf-calc" :class="parseFloat(ratio) > 2.5 ? 'warn' : 'ok'">
                    <strong>{{ ratio }} pers./hab.</strong>
                    {{ parseFloat(ratio) > 2.5 ? 'Hacinamiento (> 2,5)' : 'Sin hacinamiento' }}
                </div>
            </div>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Traslado</p>
        <div class="uf-grid">
            <AppFormField label="Tiempo de traslado" :col="6">
                <select
                    class="uf-select"
                    :value="data.commuteId ?? ''"
                    @change="setField('commuteId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="c in catalogData.commuteTimes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </AppFormField>
            <AppFormField label="Medio de transporte" :col="6">
                <select
                    class="uf-select"
                    :value="data.transportId ?? ''"
                    @change="setField('transportId', Number(($event.target as HTMLSelectElement).value) || undefined)"
                >
                    <option value="">Seleccionar</option>
                    <option v-for="t in catalogData.transportTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </AppFormField>
        </div>
    </div>

    <div class="uf-subsection">
        <p class="uf-sub-title">Servicios básicos</p>
        <div class="uf-services">
            <label
                v-for="svc in SERVICES"
                :key="svc.key as string"
                class="uf-service-tile"
                :class="{ checked: !!data[svc.key] }"
            >
                <input
                    type="checkbox"
                    :checked="!!data[svc.key]"
                    @change="setField(svc.key, ($event.target as HTMLInputElement).checked)"
                />
                <span class="lbl">{{ svc.label }}</span>
                <span class="pip" />
            </label>
        </div>
        <div class="uf-grid" style="margin-top: 12px;">
            <AppFormField label="Otros servicios" optional :col="12">
                <input class="uf-input" :value="data.otherServices" @input="setField('otherServices', ($event.target as HTMLInputElement).value)" />
            </AppFormField>
        </div>
    </div>
</template>
