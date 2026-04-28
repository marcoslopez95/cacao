<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreatePeriodModal from '@/components/scheduling/CreatePeriodModal.vue'
import DeletePeriodModal from '@/components/scheduling/DeletePeriodModal.vue'
import EditPeriodModal from '@/components/scheduling/EditPeriodModal.vue'
import LapsesPanel from '@/components/scheduling/LapsesPanel.vue'
import { usePeriodFilters } from '@/composables/filters/usePeriodFilters'
import { usePeriodForm } from '@/composables/forms/usePeriodForm'
import { usePeriodPermissions } from '@/composables/permissions/usePeriodPermissions'
import { index } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

type Props = {
    periods: Period[]
    filters: { type: Period['type'] | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Períodos', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete, canActivate, canClose } = usePeriodPermissions()
const { activate, close } = usePeriodForm()
const { type, applyFilter } = usePeriodFilters(props.filters.type)

const showCreate = ref(false)
const editingPeriod = ref<Period | null>(null)
const deletingPeriod = ref<Period | null>(null)
</script>

<template>
    <Head title="Períodos" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Períodos
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Ciclos académicos con rango de fechas y estado
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo período
            </Button>
        </div>

        <div>
            <select v-model="type" class="input" style="max-width:200px;" aria-label="Filtrar por tipo" @change="applyFilter">
                <option :value="null">Todos los tipos</option>
                <option value="semester">Semestral</option>
                <option value="year">Anual</option>
                <option value="trimester">Trimestral</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="period in periods" :key="period.id">
                    <tr>
                        <td style="font-weight:500;">
                            {{ period.name }}
                            <LapsesPanel
                                v-if="period.type === 'year'"
                                :period="period"
                                :can-create="can.create"
                                :can-update="can.update"
                                :can-delete="can.delete"
                            />
                        </td>
                        <td style="color:var(--text-secondary);">{{ period.typeLabel }}</td>
                        <td style="color:var(--text-secondary);">{{ period.startDate }} — {{ period.endDate }}</td>
                        <td>
                            <span
                                style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:4px;font-size:var(--text-xs);font-weight:500;"
                                :style="{
                                    background: period.status === 'active'   ? 'var(--color-success-light)' :
                                                period.status === 'upcoming' ? 'var(--color-info-light)'    :
                                                'var(--gris-borde)',
                                    color:      period.status === 'active'   ? 'var(--color-success)'       :
                                                period.status === 'upcoming' ? 'var(--color-info)'          :
                                                'var(--gris)',
                                }"
                            >
                                {{ period.statusLabel }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canActivate && period.status === 'upcoming'"
                                    variant="ghost"
                                    size="sm"
                                    @click="activate.submit({ period })"
                                >
                                    Activar
                                </Button>
                                <Button
                                    v-if="canClose && period.status === 'active'"
                                    variant="ghost"
                                    size="sm"
                                    @click="close.submit({ period })"
                                >
                                    Cerrar
                                </Button>
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${period.name}`"
                                    @click="editingPeriod = period"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${period.name}`"
                                    @click="deletingPeriod = period"
                                />
                            </div>
                        </td>
                    </tr>
                    </template>
                    <tr v-if="!periods.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay períodos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreatePeriodModal v-model:open="showCreate" />

    <EditPeriodModal
        v-if="editingPeriod"
        :open="!!editingPeriod"
        :period="editingPeriod"
        @update:open="v => { if (!v) editingPeriod = null }"
    />

    <DeletePeriodModal
        v-if="deletingPeriod"
        :open="!!deletingPeriod"
        :period="deletingPeriod"
        @update:open="v => { if (!v) deletingPeriod = null }"
    />
</template>
