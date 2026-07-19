import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { index } from '@/routes/academic/guardians'

type GuardianFilters = {
    search?: string
}

export function useGuardianFilters(initial: GuardianFilters, perPage: number) {
    const search = ref(initial.search ?? '')
    let debounceTimer: ReturnType<typeof setTimeout>

    function applyFilters(): void {
        router.get(
            index.url(),
            {
                search:   search.value || undefined,
                per_page: perPage !== 25 ? perPage : undefined,
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
    }))

    return { search, applyFilters, onSearchInput, paginationFilters }
}
