<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import AppBadge from '@/components/UI/AppBadge.vue'
import CreateProfessorModal from '@/components/scheduling/CreateProfessorModal.vue'
import DeleteProfessorModal from '@/components/scheduling/DeleteProfessorModal.vue'
import EditProfessorModal from '@/components/scheduling/EditProfessorModal.vue'
import { useProfessorForm } from '@/composables/forms/useProfessorForm'
import { useProfessorPermissions } from '@/composables/permissions/useProfessorPermissions'
import { index } from '@/routes/scheduling/professors'
import type { AvailableUser, Professor, ProfessorCollection } from '@/types/scheduling'

type Props = {
    professors: ProfessorCollection
    availableUsers: AvailableUser[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Profesores', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useProfessorPermissions()
const {} = useProfessorForm()

const showCreate = ref(false)
const editingProfessor = ref<Professor | null>(null)
const deletingProfessor = ref<Professor | null>(null)
</script>

<template>
    <Head title="Profesores" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Profesores
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Perfiles académicos de usuarios con rol Profesor
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo profesor
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Límite h/semana</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="professors.length === 0">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay profesores registrados.
                        </td>
                    </tr>
                    <tr v-for="professor in professors" :key="professor.id">
                        <td style="font-weight:500;">{{ professor.user.name }}</td>
                        <td style="color:var(--text-secondary);">{{ professor.user.email }}</td>
                        <td>{{ professor.weeklyHourLimit }}h</td>
                        <td>
                            <AppBadge :variant="professor.active ? 'success' : 'neutral'">
                                {{ professor.active ? 'Activo' : 'Inactivo' }}
                            </AppBadge>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:8px;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingProfessor = professor" />
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingProfessor = professor" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateProfessorModal
        :open="showCreate"
        :available-users="availableUsers"
        @update:open="showCreate = $event"
    />

    <EditProfessorModal
        v-if="editingProfessor"
        :professor="editingProfessor"
        :open="editingProfessor !== null"
        @update:open="editingProfessor = $event ? editingProfessor : null"
    />

    <DeleteProfessorModal
        v-if="deletingProfessor"
        :professor="deletingProfessor"
        :open="deletingProfessor !== null"
        @update:open="deletingProfessor = $event ? deletingProfessor : null"
    />
</template>
