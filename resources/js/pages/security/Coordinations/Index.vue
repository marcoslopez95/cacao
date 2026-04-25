<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import Pagination from '@/components/base/Pagination.vue'
import AssignCoordinatorModal from '@/components/security/AssignCoordinatorModal.vue'
import CoordinationHistoryModal from '@/components/security/CoordinationHistoryModal.vue'
import CreateCoordinationModal from '@/components/security/CreateCoordinationModal.vue'
import DeleteCoordinationModal from '@/components/security/DeleteCoordinationModal.vue'
import EditCoordinationModal from '@/components/security/EditCoordinationModal.vue'
import { usePermission } from '@/composables/usePermission'
import { index } from '@/routes/security/coordinations'
import type { CoordinationPaginator, CoordinationRow } from '@/types/security'

type Props = {
    coordinations: CoordinationPaginator
    coordinators: { id: number; name: string }[]
    careers: { id: number; name: string }[]
    filters: { search?: string; type?: string; education_level?: string; status?: string }
    can: { create: boolean; update: boolean; delete: boolean; assign: boolean; viewHistory: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Coordinaciones', href: index.url() },
        ],
    },
})

const { can } = usePermission()

const search = ref(props.filters.search ?? '')
const typeFilter = ref(props.filters.type ?? '')
const levelFilter = ref(props.filters.education_level ?? '')
const statusFilter = ref(props.filters.status ?? '')

let debounceTimer: ReturnType<typeof setTimeout>

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            education_level: levelFilter.value || undefined,
            status: statusFilter.value || undefined,
            per_page: props.coordinations.per_page !== 20 ? props.coordinations.per_page : undefined,
        },
        { preserveState: true, replace: true },
    )
}

function onSearchInput(): void {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
}

const paginationFilters = computed(() => ({
    search: search.value || undefined,
    type: typeFilter.value || undefined,
    education_level: levelFilter.value || undefined,
    status: statusFilter.value || undefined,
}))

const showCreate = ref(false)
const editingCoordination = ref<CoordinationRow | null>(null)
const assigningCoordination = ref<CoordinationRow | null>(null)
const historyCoordination = ref<CoordinationRow | null>(null)
const deletingCoordination = ref<CoordinationRow | null>(null)

function typeLabel(type: string): string {
    const labels: Record<string, string> = { career: 'Carrera', grade: 'Año escolar', academic: 'Académica' }
    return labels[type] ?? type
}

function levelLabel(level: string): string {
    const labels: Record<string, string> = { university: 'Universitario', secondary: 'Media / Básica' }
    return labels[level] ?? level
}
</script>

<template>
    <Head title="Coordinaciones" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Coordinaciones
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona las coordinaciones académicas del sistema
                </p>
            </div>
            <Button v-if="props.can.create" variant="primary" icon="plus" @click="showCreate = true">
                Nueva coordinación
            </Button>
        </div>

        <div class="table-wrap">
            <div style="display:flex;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border);">
                <input
                    v-model="search"
                    class="input"
                    type="search"
                    placeholder="Buscar coordinación..."
                    style="flex:1;min-width:200px;max-width:320px;"
                    @input="onSearchInput"
                />
                <select v-model="typeFilter" class="input" style="width:160px;" @change="applyFilters">
                    <option value="">Todos los tipos</option>
                    <option value="career">Carrera</option>
                    <option value="grade">Año escolar</option>
                </select>
                <select v-model="levelFilter" class="input" style="width:180px;" @change="applyFilters">
                    <option value="">Todos los niveles</option>
                    <option value="university">Universitario</option>
                    <option value="secondary">Media / Básica</option>
                </select>
                <select v-model="statusFilter" class="input" style="width:160px;" @change="applyFilters">
                    <option value="">Todos los estados</option>
                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Nivel</th>
                        <th>Coordinador actual</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in props.coordinations.data" :key="c.id">
                        <td style="font-weight:500;">{{ c.name }}</td>
                        <td style="color:var(--text-secondary);">{{ typeLabel(c.type) }}</td>
                        <td style="color:var(--text-secondary);">{{ levelLabel(c.education_level) }}</td>
                        <td>
                            <span v-if="c.current_coordinator">{{ c.current_coordinator.name }}</span>
                            <Badge v-else variant="ghost">Sin asignar</Badge>
                        </td>
                        <td>
                            <Badge :variant="c.active ? 'success' : 'neutral'" dot>
                                {{ c.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="can('coordinations.edit')"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${c.name}`"
                                    @click="editingCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.assign')"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="user-check"
                                    :aria-label="`Asignar coordinador a ${c.name}`"
                                    @click="assigningCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.view_history')"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="clock"
                                    :aria-label="`Ver historial de ${c.name}`"
                                    @click="historyCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.delete')"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${c.name}`"
                                    @click="deletingCoordination = c"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.coordinations.data.length">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay coordinaciones registradas.
                        </td>
                    </tr>
                </tbody>
            </table>

            <Pagination
                :paginator="props.coordinations"
                :route-url="index.url()"
                :filters="paginationFilters"
            />
        </div>
    </div>

    <CreateCoordinationModal v-model:open="showCreate" :careers="props.careers" />

    <EditCoordinationModal
        v-if="editingCoordination"
        :open="!!editingCoordination"
        :coordination="editingCoordination"
        :careers="props.careers"
        @update:open="v => { if (!v) editingCoordination = null }"
    />

    <AssignCoordinatorModal
        v-if="assigningCoordination"
        :open="!!assigningCoordination"
        :coordination="assigningCoordination"
        :coordinators="props.coordinators"
        @update:open="v => { if (!v) assigningCoordination = null }"
    />

    <CoordinationHistoryModal
        v-if="historyCoordination"
        :open="!!historyCoordination"
        :coordination="historyCoordination"
        @update:open="v => { if (!v) historyCoordination = null }"
    />

    <DeleteCoordinationModal
        v-if="deletingCoordination"
        :open="!!deletingCoordination"
        :coordination="deletingCoordination"
        @update:open="v => { if (!v) deletingCoordination = null }"
    />
</template>
