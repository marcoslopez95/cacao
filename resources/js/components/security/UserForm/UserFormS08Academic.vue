<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'
import { UF_ACADEMIC_STATUSES, UF_STUDY_MODALITIES, UF_SHIFTS, UF_ADMISSION, UF_GRADES } from '@/types/userFormCatalogs'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppPillRadios from '@/components/UI/AppPillRadios.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Código de estudiante" admin-only :col="4">
            <input
                class="uf-input"
                :value="data.studentCode"
                placeholder="2026-1234"
                @input="setField('studentCode', ($event.target as HTMLInputElement).value)"
            />
        </AppFormField>

        <AppFormField label="Estatus académico" required :col="4">
            <select
                class="uf-select"
                :value="data.academicStatus ?? ''"
                @change="setField('academicStatus', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">Seleccionar</option>
                <option v-for="s in UF_ACADEMIC_STATUSES" :key="s" :value="s">{{ s }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Fecha de inscripción" required :col="4">
            <input
                class="uf-input"
                type="date"
                :value="data.enrollDate"
                @input="setField('enrollDate', ($event.target as HTMLInputElement).value)"
            />
        </AppFormField>

        <AppFormField label="Modalidad de estudio" :col="4">
            <select
                class="uf-select"
                :value="data.modality ?? ''"
                @change="setField('modality', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">Seleccionar</option>
                <option v-for="m in UF_STUDY_MODALITIES" :key="m" :value="m">{{ m }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Turno" :col="4">
            <AppPillRadios
                :model-value="data.shift ?? ''"
                :options="UF_SHIFTS"
                @update:model-value="setField('shift', $event)"
            />
        </AppFormField>

        <AppFormField label="Tipo de admisión" :col="4">
            <select
                class="uf-select"
                :value="data.admission ?? ''"
                @change="setField('admission', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">Seleccionar</option>
                <option v-for="a in UF_ADMISSION" :key="a" :value="a">{{ a }}</option>
            </select>
        </AppFormField>

        <AppFormField label="Promedio (GPA)" :col="4">
            <div class="uf-input-wrap has-right">
                <input
                    class="uf-input"
                    type="number"
                    min="0"
                    max="20"
                    step="0.01"
                    :value="data.gpa"
                    placeholder="0.00"
                    @input="setField('gpa', ($event.target as HTMLInputElement).value)"
                />
                <span class="uf-ico-right" style="pointer-events:none;font-size:11px;color:var(--text-muted);">/ 20</span>
            </div>
        </AppFormField>

        <AppFormField label="Grado / año" :col="4">
            <select
                class="uf-select"
                :value="data.grade ?? ''"
                @change="setField('grade', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">Seleccionar</option>
                <option v-for="g in UF_GRADES" :key="g" :value="g">{{ g }}</option>
            </select>
        </AppFormField>
    </div>
</template>
