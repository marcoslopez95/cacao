import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

export function useLapseForm() {
    const storeOps = {
        form() {
            return useForm({
                number:     '' as unknown as number,
                name:       '',
                start_date: '',
                end_date:   '',
            })
        },
        url({ period }: { period: Period }) {
            return store.url({ period })
        },
    }

    const updateOps = {
        form({ lapse }: { lapse: Lapse }) {
            return useForm({
                number:     lapse.number,
                name:       lapse.name,
                start_date: lapse.startDate,
                end_date:   lapse.endDate,
            })
        },
        url({ period, lapse }: { period: Period; lapse: Lapse }) {
            return update.url({ period, lapse })
        },
    }

    const removeOps = {
        submit({ period, lapse }: { period: Period; lapse: Lapse }): void {
            useForm({}).delete(destroy.url({ period, lapse }))
        },
    }

    return {
        store:  storeOps,
        update: updateOps,
        remove: removeOps,
    }
}
