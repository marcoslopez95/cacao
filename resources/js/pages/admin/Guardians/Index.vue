<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppIcon from '@/components/UI/AppIcon.vue'
import Pagination from '@/components/UI/AppPagination.vue'
import { useGuardianFilters } from '@/composables/filters/useGuardianFilters'
import { index, show as showGuardian } from '@/routes/academic/guardians'
import type { GuardianCollection } from '@/types/guardian'

type Props = {
    guardians: GuardianCollection
    filters: { search?: string }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Representantes' },
        ],
    },
})

const { search, onSearchInput, paginationFilters } =
    useGuardianFilters(props.filters, props.guardians.meta.per_page)
</script>

<template>
    <Head title="Representantes" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div>
            <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                Representantes
            </h1>
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                Estudiantes a cargo de cada representante
            </p>
        </div>

        <div class="table-wrap">
            <div style="display:flex;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border);">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre o correo..."
                    class="input"
                    style="flex:1;min-width:200px;max-width:320px;"
                    @input="onSearchInput"
                />
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Representante</th>
                        <th>Estudiantes a cargo</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="guardian in props.guardians.data" :key="guardian.id">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-soft);color:var(--accent);border:1.5px solid color-mix(in srgb,var(--accent) 25%,transparent);display:grid;place-items:center;font-weight:600;font-size:13px;flex-shrink:0;">
                                    {{ guardian.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div style="font-weight:500;color:var(--text-primary);">{{ guardian.name }}</div>
                                    <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ guardian.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-primary);">{{ guardian.students_count }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Link
                                    :href="showGuardian({ guardian: guardian.id }).url"
                                    class="btn btn-ghost btn-sm btn-icon"
                                    :aria-label="`Ver estudiantes de ${guardian.name}`"
                                >
                                    <AppIcon name="eye" :size="13" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.guardians.data.length">
                        <td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay representantes que coincidan con la búsqueda
                        </td>
                    </tr>
                </tbody>
            </table>

            <Pagination
                :paginator="props.guardians.meta"
                :route-url="index.url()"
                :filters="paginationFilters"
            />
        </div>
    </div>
</template>
