<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateUniversitySectionModal from '@/components/scheduling/CreateUniversitySectionModal.vue'
import DeleteSectionModal from '@/components/scheduling/DeleteSectionModal.vue'
import EditUniversitySectionModal from '@/components/scheduling/EditUniversitySectionModal.vue'
import { useUniversitySectionFilters } from '@/composables/filters/useUniversitySectionFilters'
import { useUniversitySectionForm } from '@/composables/forms/useUniversitySectionForm'
import { useSectionPermissions } from '@/composables/permissions/useSectionPermissions'
import { index } from '@/routes/scheduling/sections/university'
import type {
    AvailablePeriod,
    ClassroomForSection,
    SubjectForSection,
    UniversitySection,
    UniversitySectionCollection,
} from '@/types/scheduling'

type Props = {
    sections: UniversitySectionCollection
    periods: AvailablePeriod[]
    subjects: SubjectForSection[]
    classrooms: ClassroomForSection[]
    filters: { period_id: number | null; subject: string | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Secciones Universitarias', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSectionPermissions()
const {} = useUniversitySectionForm()
const { periodId, subject, applyFilters } = useUniversitySectionFilters(props.filters.period_id, props.filters.subject)

const showCreate = ref(false)
const editingSection = ref<UniversitySection | null>(null)
const deletingSection = ref<UniversitySection | null>(null)
</script>

<template>
    <Head title="Secciones Universitarias" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Secciones Universitarias
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Grupos de cursado por materia y período
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
            <input
                v-model="subject"
                class="input"
                style="max-width:220px;"
                placeholder="Buscar materia..."
                @input="applyFilters"
            />
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Cód.</th>
                        <th>Período</th>
                        <th>Cupo</th>
                        <th>Aulas</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="sections.length === 0">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay secciones registradas.
                        </td>
                    </tr>
                    <tr v-for="section in sections" :key="section.id">
                        <td style="font-weight:500;">{{ section.subject.code }} — {{ section.subject.name }}</td>
                        <td>{{ section.code }}</td>
                        <td style="color:var(--text-secondary);">{{ section.period.name }}</td>
                        <td>{{ section.capacity }}</td>
                        <td style="color:var(--text-secondary);font-size:var(--text-xs);">
                            <span v-if="section.theoryClassroom">T: {{ section.theoryClassroom.identifier }}</span>
                            <span v-if="section.theoryClassroom && section.labClassroom"> · </span>
                            <span v-if="section.labClassroom">L: {{ section.labClassroom.identifier }}</span>
                            <span v-if="!section.theoryClassroom && !section.labClassroom">—</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:8px;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingSection = section" />
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingSection = section" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateUniversitySectionModal
        :open="showCreate"
        :periods="periods"
        :subjects="subjects"
        :classrooms="classrooms"
        @update:open="showCreate = $event"
    />

    <EditUniversitySectionModal
        v-if="editingSection"
        :section="editingSection"
        :open="editingSection !== null"
        :classrooms="classrooms"
        @update:open="editingSection = $event ? editingSection : null"
    />

    <DeleteSectionModal
        v-if="deletingSection"
        :section="deletingSection"
        :open="deletingSection !== null"
        @update:open="deletingSection = $event ? deletingSection : null"
    />
</template>
