import { useForm } from '@inertiajs/vue3'
import { activate, close, destroy, store, update } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

export function usePeriodForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    name:       '',
                    type:       'semester' as Period['type'],
                    start_date: '',
                    end_date:   '',
                }),
            }
        },
    }

    const updateOps = {
        form({ period }: { period: Period }) {
            return {
                url:    update.url({ period }),
                method: 'patch' as const,
                data:   useForm({
                    name:       period.name,
                    type:       period.type,
                    start_date: period.startDate,
                    end_date:   period.endDate,
                }),
            }
        },
    }

    const removeOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).delete(destroy.url({ period }))
        },
    }

    const activateOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).patch(activate.url({ period }))
        },
    }

    const closeOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).patch(close.url({ period }))
        },
    }

    return {
        store:    storeOps,
        update:   updateOps,
        remove:   removeOps,
        activate: activateOps,
        close:    closeOps,
    }
}
