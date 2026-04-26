<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreateCareerModal from '@/components/academic/CreateCareerModal.vue'
import DeleteCareerModal from '@/components/academic/DeleteCareerModal.vue'
import EditCareerModal from '@/components/academic/EditCareerModal.vue'
import { useCareerPermissions } from '@/composables/permissions/useCareerPermissions'
import { index } from '@/routes/academic/careers'
import type { Career, CareerCategory } from '@/types/academic'

type Props = {
    careers: Career[]
    categories: CareerCategory[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Carreras', href: index.url() },
        ],
    },
})

const { canCreate, canUpdate, canDelete } = useCareerPermissions()

const showCreate = ref(false)
const editingCareer = ref<Career | null>(null)
const deletingCareer = ref<Career | null>(null)

const selectedCategoryId = ref<number | ''>('')

const filteredCareers = computed(() => {
    if (selectedCategoryId.value === '') {
        return props.careers
    }

    return props.careers.filter((c) => c.category.id === selectedCategoryId.value)
})
</script>

<template>
    <Head title="Carreras" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Carreras
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de carreras agrupadas por categoría
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nueva carrera
            </Button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <select
                v-model="selectedCategoryId"
                class="input"
                style="max-width:240px;"
            >
                <option value="">Todas las categorías</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Pensums</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="career in filteredCareers" :key="career.id">
                        <td style="font-weight:500;">{{ career.name }}</td>
                        <td>
                            <Badge variant="neutral">{{ career.code }}</Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ career.category.name }}</td>
                        <td>
                            <Badge :variant="career.active ? 'success' : 'neutral'" dot>
                                {{ career.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ career.pensumsCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    icon="book-open"
                                    :aria-label="`Ver pensums de ${career.name}`"
                                    disabled
                                    title="Disponible en la Parte 3"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${career.name}`"
                                    @click="editingCareer = career"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${career.name}`"
                                    @click="deletingCareer = career"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredCareers.length">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay carreras registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateCareerModal
        v-model:open="showCreate"
        :categories="categories"
    />

    <EditCareerModal
        v-if="editingCareer"
        :open="!!editingCareer"
        :career="editingCareer"
        :categories="categories"
        @update:open="v => { if (!v) editingCareer = null }"
    />

    <DeleteCareerModal
        v-if="deletingCareer"
        :open="!!deletingCareer"
        :career="deletingCareer"
        @update:open="v => { if (!v) deletingCareer = null }"
    />
</template>
