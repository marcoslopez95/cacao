<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreateClassroomModal from '@/components/infrastructure/CreateClassroomModal.vue'
import DeleteClassroomModal from '@/components/infrastructure/DeleteClassroomModal.vue'
import EditClassroomModal from '@/components/infrastructure/EditClassroomModal.vue'
import { useClassroomFilters } from '@/composables/filters/useClassroomFilters'
import { useClassroomPermissions } from '@/composables/permissions/useClassroomPermissions'
import type { Building, Classroom } from '@/types/infrastructure'

type Props = {
    classrooms: Classroom[]
    buildings: Building[]
    filters: { buildingId: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Infraestructura', href: '#' },
        { title: 'Aulas', href: '#' },
    ],
})

const { canCreate, canUpdate, canDelete } = useClassroomPermissions()
const { buildingId, applyFilter } = useClassroomFilters(props.filters.buildingId)

const selectedClassroom = ref<Classroom | null>(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

function openEdit(classroom: Classroom): void {
    selectedClassroom.value = classroom
    showEditModal.value = true
}

function openDelete(classroom: Classroom): void {
    selectedClassroom.value = classroom
    showDeleteModal.value = true
}

const typeLabel: Record<string, string> = {
    theory:     'Teórica',
    laboratory: 'Laboratorio',
}
</script>

<template>
    <Head title="Aulas" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Aulas
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de aulas de la institución
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreateModal = true">
                Nueva aula
            </Button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <select
                v-model.number="buildingId"
                class="input"
                style="max-width:220px;"
                @change="applyFilter"
            >
                <option :value="null">Todos los edificios</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Identificador</th>
                        <th>Edificio</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="classroom in classrooms" :key="classroom.id">
                        <td style="font-weight:500;">{{ classroom.identifier }}</td>
                        <td style="color:var(--text-secondary);">{{ classroom.building.name }}</td>
                        <td>
                            <Badge :variant="classroom.type === 'theory' ? 'neutral' : 'info'">
                                {{ typeLabel[classroom.type] }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ classroom.capacity }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${classroom.identifier}`"
                                    @click="openEdit(classroom)"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${classroom.identifier}`"
                                    @click="openDelete(classroom)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!classrooms.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay aulas registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateClassroomModal
        v-model:open="showCreateModal"
        :buildings="buildings"
    />

    <EditClassroomModal
        v-if="selectedClassroom && showEditModal"
        :open="showEditModal"
        :classroom="selectedClassroom"
        :buildings="buildings"
        @update:open="v => { if (!v) { showEditModal = false; selectedClassroom = null } }"
    />

    <DeleteClassroomModal
        v-if="selectedClassroom && showDeleteModal"
        :open="showDeleteModal"
        :classroom="selectedClassroom"
        @update:open="v => { if (!v) { showDeleteModal = false; selectedClassroom = null } }"
    />
</template>
