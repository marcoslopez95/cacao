// resources/js/composables/filters/useSchoolSectionFilters.ts
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { index } from '@/routes/scheduling/sections/school'

export function useSchoolSectionFilters(
    initialPeriodId: number | null,
    initialPensumId: number | null,
) {
    const periodId = ref<number | null>(initialPeriodId)
    const pensumId = ref<number | null>(initialPensumId)

    function applyFilters(): void {
        router.get(
            index.url(),
            { period_id: periodId.value ?? undefined, pensum_id: pensumId.value ?? undefined },
            { preserveState: true, replace: true },
        )
    }

    return { periodId, pensumId, applyFilters }
}
