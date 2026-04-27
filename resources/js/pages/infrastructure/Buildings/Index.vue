<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateBuildingModal from '@/components/infrastructure/CreateBuildingModal.vue'
import DeleteBuildingModal from '@/components/infrastructure/DeleteBuildingModal.vue'
import EditBuildingModal from '@/components/infrastructure/EditBuildingModal.vue'
import { useBuildingPermissions } from '@/composables/permissions/useBuildingPermissions'
import { index } from '@/routes/infrastructure/buildings'
import type { Building } from '@/types/infrastructure'

type Props = {
    buildings: Building[]
    can: { create: boolean; update: boolean; delete: boolean }
}

defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Infraestructura', href: '#' },
            { title: 'Edificios', href: index.url() },
        ],
    },
})

const { canCreate, canUpdate, canDelete } = useBuildingPermissions()

const showCreate = ref(false)
const editingBuilding = ref<Building | null>(null)
const deletingBuilding = ref<Building | null>(null)
</script>

<template>
    <Head title="Edificios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Edificios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de edificios de la institución
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo edificio
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Aulas</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="building in buildings" :key="building.id">
                        <td style="font-weight:500;">{{ building.name }}</td>
                        <td style="color:var(--text-secondary);">{{ building.classroomsCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${building.name}`"
                                    @click="editingBuilding = building"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${building.name}`"
                                    @click="deletingBuilding = building"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!buildings.length">
                        <td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay edificios registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateBuildingModal v-model:open="showCreate" />

    <EditBuildingModal
        v-if="editingBuilding"
        :open="!!editingBuilding"
        :building="editingBuilding"
        @update:open="v => { if (!v) editingBuilding = null }"
    />

    <DeleteBuildingModal
        v-if="deletingBuilding"
        :open="!!deletingBuilding"
        :building="deletingBuilding"
        @update:open="v => { if (!v) deletingBuilding = null }"
    />
</template>
