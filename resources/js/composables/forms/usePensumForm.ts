import { router } from '@inertiajs/vue3'
import { update } from '@/routes/academic/pensums'
import type { Career, Pensum } from '@/types/academic'

export function usePensumForm(career: Career) {
    function toggle(pensum: Pensum): void {
        router.patch(update.url({ career, pensum }), {
            name:          pensum.name,
            period_type:   pensum.periodType,
            total_periods: pensum.totalPeriods,
            is_active:     !pensum.isActive,
        })
    }

    return { toggle }
}
