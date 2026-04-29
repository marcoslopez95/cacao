<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateSchoolSectionModal from '@/components/scheduling/CreateSchoolSectionModal.vue'
import DeleteSchoolSectionModal from '@/components/scheduling/DeleteSchoolSectionModal.vue'
import EditSchoolSectionModal from '@/components/scheduling/EditSchoolSectionModal.vue'
import { useSchoolSectionFilters } from '@/composables/filters/useSchoolSectionFilters'
import { useSchoolSectionForm } from '@/composables/forms/useSchoolSectionForm'
import { useSectionPermissions } from '@/composables/permissions/useSectionPermissions'
import { index } from '@/routes/scheduling/sections/school'
import type {
    AvailablePeriod,
    PensumForSection,
    ProfessorForSection,
    SchoolSection,
    SchoolSectionClassroom,
    SchoolSectionCollection,
} from '@/types/scheduling'

type Props = {
    sections: SchoolSectionCollection
    periods: AvailablePeriod[]
    pensums: PensumForSection[]
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
    filters: { period_id: number | null; pensum_id: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Secciones Escolares', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSectionPermissions()
const {} = useSchoolSectionForm()
const { periodId, pensumId, applyFilters } = useSchoolSectionFilters(props.filters.period_id, props.filters.pensum_id)

const showCreate = ref(false)
const editingSection = ref<SchoolSection | null>(null)
const deletingSection = ref<SchoolSection | null>(null)
</script>

<template>
    <Head title="Secciones Escolares" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Secciones Escolares
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Grupos de cursado para educación primaria y secundaria
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nueva sección
            </Button>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <select v-model="periodId" class="input" style="max-width:200px;" aria-label="Filtrar por período" @change="applyFilters">
                <option :value="null">Todos los períodos</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="pensumId" class="input" style="max-width:240px;" aria-label="Filtrar por pensum" @change="applyFilters">
                <option :value="null">Todos los pensums</option>
                <option v-for="p in pensums" :key="p.id" :value="p.id">{{ p.career.name }} — {{ p.name }}</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th>Período</th>
                        <th>Carrera / Pensum</th>
                        <th>Docente</th>
                        <th>Cupo</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="sections.length === 0">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay secciones escolares registradas.
                        </td>
                    </tr>
                    <tr v-for="section in sections" :key="section.id">
                        <td style="font-weight:500;">{{ section.grade }}° {{ section.letter }}</td>
                        <td style="color:var(--text-secondary);">{{ section.period.name }}</td>
                        <td style="color:var(--text-secondary);">{{ section.pensum.career.name }}</td>
                        <td style="color:var(--text-secondary);">{{ section.mainTeacher?.user.name ?? '—' }}</td>
                        <td>{{ section.capacity }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingSection = section">
                                    Editar
                                </Button>
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingSection = section">
                                    Eliminar
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <CreateSchoolSectionModal
            v-model:open="showCreate"
            :periods="periods"
            :pensums="pensums"
            :professors="professors"
            :classrooms="classrooms"
        />

        <EditSchoolSectionModal
            v-if="editingSection"
            :open="editingSection !== null"
            :section="editingSection"
            :professors="professors"
            :classrooms="classrooms"
            @update:open="(v) => { if (! v) editingSection = null }"
        />

        <DeleteSchoolSectionModal
            v-if="deletingSection"
            :open="deletingSection !== null"
            :section="deletingSection"
            @update:open="(v) => { if (! v) deletingSection = null }"
        />
    </div>
</template>
