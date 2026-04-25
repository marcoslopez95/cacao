import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/security/coordinations'

type CoordinationFilters = {
    search?: string
    type?: string
    education_level?: string
    status?: string
}

export function useCoordinationFilters(initial: CoordinationFilters, perPage: number) {
    const search       = ref(initial.search ?? '')
    const typeFilter   = ref(initial.type ?? '')
    const levelFilter  = ref(initial.education_level ?? '')
    const statusFilter = ref(initial.status ?? '')
    let debounceTimer: ReturnType<typeof setTimeout>

    function applyFilters(): void {
        router.get(
            index.url(),
            {
                search:          search.value || undefined,
                type:            typeFilter.value || undefined,
                education_level: levelFilter.value || undefined,
                status:          statusFilter.value || undefined,
                per_page:        perPage !== 20 ? perPage : undefined,
            },
            { preserveState: true, replace: true },
        )
    }

    function onSearchInput(): void {
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(applyFilters, 350)
    }

    const paginationFilters = computed(() => ({
        search:          search.value || undefined,
        type:            typeFilter.value || undefined,
        education_level: levelFilter.value || undefined,
        status:          statusFilter.value || undefined,
    }))

    return { search, typeFilter, levelFilter, statusFilter, applyFilters, onSearchInput, paginationFilters }
}
