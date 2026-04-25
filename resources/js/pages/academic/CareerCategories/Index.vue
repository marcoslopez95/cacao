<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateCareerCategoryModal from '@/components/academic/CreateCareerCategoryModal.vue'
import DeleteCareerCategoryModal from '@/components/academic/DeleteCareerCategoryModal.vue'
import EditCareerCategoryModal from '@/components/academic/EditCareerCategoryModal.vue'
import { useCareerCategoryPermissions } from '@/composables/permissions/useCareerCategoryPermissions'
import { index } from '@/routes/academic/career-categories'
import type { CareerCategory } from '@/types/academic'

type Props = {
    categories: CareerCategory[]
    can: { create: boolean; update: boolean; delete: boolean }
}

defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Categorías de carreras', href: index.url() },
        ],
    },
})

const { canCreate, canUpdate, canDelete } = useCareerCategoryPermissions()

const showCreate = ref(false)
const editingCategory = ref<CareerCategory | null>(null)
const deletingCategory = ref<CareerCategory | null>(null)
</script>

<template>
    <Head title="Categorías de carreras" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Categorías de carreras
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Agrupaciones para organizar las carreras del sistema
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nueva categoría
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="category in categories" :key="category.id">
                        <td style="font-weight:500;">{{ category.name }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${category.name}`"
                                    @click="editingCategory = category"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${category.name}`"
                                    @click="deletingCategory = category"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!categories.length">
                        <td colspan="2" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay categorías registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateCareerCategoryModal v-model:open="showCreate" />

    <EditCareerCategoryModal
        v-if="editingCategory"
        :open="!!editingCategory"
        :category="editingCategory"
        @update:open="v => { if (!v) editingCategory = null }"
    />

    <DeleteCareerCategoryModal
        v-if="deletingCategory"
        :open="!!deletingCategory"
        :category="deletingCategory"
        @update:open="v => { if (!v) deletingCategory = null }"
    />
</template>
