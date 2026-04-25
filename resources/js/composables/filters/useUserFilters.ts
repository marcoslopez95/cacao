import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/security/users'

type UserFilters = {
    search?: string
    role?: string
    status?: string
}

export function useUserFilters(initial: UserFilters, perPage: number) {
    const search       = ref(initial.search ?? '')
    const roleFilter   = ref(initial.role ?? '')
    const statusFilter = ref(initial.status ?? '')
    let debounceTimer: ReturnType<typeof setTimeout>

    function applyFilters(): void {
        router.get(
            index.url(),
            {
                search:   search.value || undefined,
                role:     roleFilter.value || undefined,
                status:   statusFilter.value || undefined,
                per_page: perPage !== 20 ? perPage : undefined,
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
        role:   roleFilter.value || undefined,
        status: statusFilter.value || undefined,
    }))

    return { search, roleFilter, statusFilter, applyFilters, onSearchInput, paginationFilters }
}
