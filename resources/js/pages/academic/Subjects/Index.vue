<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreateSubjectModal from '@/components/academic/CreateSubjectModal.vue'
import DeleteSubjectModal from '@/components/academic/DeleteSubjectModal.vue'
import EditSubjectModal from '@/components/academic/EditSubjectModal.vue'
import PrerequisitesModal from '@/components/academic/PrerequisitesModal.vue'
import { useSubjectPermissions } from '@/composables/permissions/useSubjectPermissions'
import { index as careersIndex } from '@/routes/academic/careers'
import { index as pensumsIndex } from '@/routes/academic/pensums'
import type { Career, Pensum, Subject } from '@/types/academic'

type Props = {
    career: Career
    pensum: Pensum
    subjects: Subject[]
    can: {
        create: boolean
        update: boolean
        delete: boolean
        managePrerequisites: boolean
    }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Académico', href: '#' },
        { title: 'Carreras', href: careersIndex.url() },
        { title: props.career.name, href: pensumsIndex.url(props.career) },
        { title: props.pensum.name, href: '#' },
    ],
})

const { canCreate, canUpdate, canDelete, canManagePrerequisites } = useSubjectPermissions()

const selectedSubject = ref<Subject | null>(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showPrerequisitesModal = ref(false)

const selectedPeriod = ref<number | null>(null)

const filteredSubjects = computed(() => {
    if (selectedPeriod.value === null) {
        return props.subjects
    }
    return props.subjects.filter((s) => s.periodNumber === selectedPeriod.value)
})

const periods = computed(() => {
    const count = props.pensum.totalPeriods
    return Array.from({ length: count }, (_, i) => i + 1)
})

function openEdit(subject: Subject): void {
    selectedSubject.value = subject
    showEditModal.value = true
}

function openDelete(subject: Subject): void {
    selectedSubject.value = subject
    showDeleteModal.value = true
}

function openPrerequisites(subject: Subject): void {
    selectedSubject.value = subject
    showPrerequisitesModal.value = true
}
</script>

<template>
    <Head :title="`Materias — ${pensum.name}`" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Materias
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    {{ pensum.name }} — {{ career.name }}
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreateModal = true">
                Nueva materia
            </Button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <select
                v-model="selectedPeriod"
                class="input"
                style="max-width:200px;"
            >
                <option :value="null">Todos los períodos</option>
                <option v-for="period in periods" :key="period" :value="period">
                    Período {{ period }}
                </option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Período</th>
                        <th>UC</th>
                        <th>Prerrequisitos</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="subject in filteredSubjects" :key="subject.id">
                        <td style="font-weight:500;">{{ subject.name }}</td>
                        <td>
                            <Badge variant="neutral">{{ subject.code }}</Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ subject.periodNumber }}</td>
                        <td style="color:var(--text-secondary);">{{ subject.creditsUc }}</td>
                        <td>
                            <div v-if="subject.prerequisites.length" style="display:flex;flex-wrap:wrap;gap:4px;">
                                <Badge
                                    v-for="prereq in subject.prerequisites"
                                    :key="prereq.id"
                                    variant="neutral"
                                >
                                    {{ prereq.code }}
                                </Badge>
                            </div>
                            <span v-else style="color:var(--text-muted);">—</span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${subject.name}`"
                                    @click="openEdit(subject)"
                                />
                                <Button
                                    v-if="canManagePrerequisites"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="git-branch"
                                    :aria-label="`Gestionar prerrequisitos de ${subject.name}`"
                                    @click="openPrerequisites(subject)"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${subject.name}`"
                                    @click="openDelete(subject)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredSubjects.length">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            <template v-if="selectedPeriod !== null">
                                No hay materias en este período.
                            </template>
                            <template v-else>
                                No hay materias registradas para este pensum.
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateSubjectModal
        v-model:open="showCreateModal"
        :career="career"
        :pensum="pensum"
    />

    <EditSubjectModal
        v-if="selectedSubject && showEditModal"
        :open="showEditModal"
        :career="career"
        :pensum="pensum"
        :subject="selectedSubject"
        @update:open="v => { if (!v) { showEditModal = false; selectedSubject = null } }"
    />

    <PrerequisitesModal
        v-if="selectedSubject && showPrerequisitesModal"
        :open="showPrerequisitesModal"
        :career="career"
        :pensum="pensum"
        :subject="selectedSubject"
        :subjects="subjects"
        @update:open="v => { if (!v) { showPrerequisitesModal = false; selectedSubject = null } }"
    />

    <DeleteSubjectModal
        v-if="selectedSubject && showDeleteModal"
        :open="showDeleteModal"
        :career="career"
        :pensum="pensum"
        :subject="selectedSubject"
        @update:open="v => { if (!v) { showDeleteModal = false; selectedSubject = null } }"
    />
</template>
