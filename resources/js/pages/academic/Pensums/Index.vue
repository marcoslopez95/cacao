<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreatePensumModal from '@/components/academic/CreatePensumModal.vue'
import DeletePensumModal from '@/components/academic/DeletePensumModal.vue'
import EditPensumModal from '@/components/academic/EditPensumModal.vue'
import { usePensumForm } from '@/composables/forms/usePensumForm'
import { usePensumPermissions } from '@/composables/permissions/usePensumPermissions'
import { index as careersIndex } from '@/routes/academic/careers'
import type { Career, Pensum } from '@/types/academic'

type Props = {
    career: Career
    pensums: Pensum[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Académico', href: '#' },
        { title: 'Carreras', href: careersIndex.url() },
        { title: props.career.name, href: '#' },
    ],
})

const { canCreate, canUpdate, canDelete } = usePensumPermissions()
const { toggle } = usePensumForm(props.career)

const showCreate = ref(false)
const editingPensum = ref<Pensum | null>(null)
const deletingPensum = ref<Pensum | null>(null)
</script>

<template>
    <Head :title="`Pensums — ${career.name}`" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Pensums
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    {{ career.name }}
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo pensum
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Períodos</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="pensum in pensums" :key="pensum.id">
                        <td style="font-weight:500;">{{ pensum.name }}</td>
                        <td style="color:var(--text-secondary);">
                            {{ pensum.periodType === 'semester' ? 'Semestral' : 'Anual' }}
                        </td>
                        <td style="color:var(--text-secondary);">{{ pensum.totalPeriods }}</td>
                        <td>
                            <Badge :variant="pensum.isActive ? 'success' : 'neutral'" dot>
                                {{ pensum.isActive ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    icon="book"
                                    icon-only
                                    :aria-label="`Ver materias de ${pensum.name}`"
                                    disabled
                                    title="Disponible en la Parte 4"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    :icon="pensum.isActive ? 'toggle-right' : 'toggle-left'"
                                    :aria-label="pensum.isActive ? `Desactivar ${pensum.name}` : `Activar ${pensum.name}`"
                                    @click="toggle(pensum)"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${pensum.name}`"
                                    @click="editingPensum = pensum"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${pensum.name}`"
                                    @click="deletingPensum = pensum"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!pensums.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay pensums registrados para esta carrera.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreatePensumModal
        v-model:open="showCreate"
        :career="career"
    />

    <EditPensumModal
        v-if="editingPensum"
        :open="!!editingPensum"
        :career="career"
        :pensum="editingPensum"
        @update:open="v => { if (!v) editingPensum = null }"
    />

    <DeletePensumModal
        v-if="deletingPensum"
        :open="!!deletingPensum"
        :career="career"
        :pensum="deletingPensum"
        @update:open="v => { if (!v) deletingPensum = null }"
    />
</template>
