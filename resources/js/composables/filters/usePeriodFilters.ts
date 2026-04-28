import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

export function usePeriodFilters(initialType: Period['type'] | null = null) {
    const type = ref<Period['type'] | null>(initialType)

    function applyFilter(): void {
        router.get(
            index.url(),
            type.value ? { type: type.value } : {},
            { preserveState: true, replace: true },
        )
    }

    return { type, applyFilter }
}
