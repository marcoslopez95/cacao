import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/periods'

export function usePeriodFilters(initialType: string | null = null) {
    const type = ref<string | null>(initialType)

    function applyFilter(): void {
        router.get(
            index.url(),
            type.value ? { type: type.value } : {},
            { preserveState: true, replace: true },
        )
    }

    return { type, applyFilter }
}
