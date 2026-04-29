import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/sections/university'

export function useUniversitySectionFilters(
    initialPeriodId: number | null,
    initialSubject: string | null,
) {
    const periodId = ref<number | null>(initialPeriodId)
    const subject  = ref<string | null>(initialSubject)

    function applyFilters(): void {
        router.get(
            index.url(),
            { period_id: periodId.value ?? undefined, subject: subject.value || undefined },
            { preserveState: true, replace: true },
        )
    }

    return { periodId, subject, applyFilters }
}
