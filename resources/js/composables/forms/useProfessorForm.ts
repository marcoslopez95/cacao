import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/professors'
import type { Professor } from '@/types/scheduling'

export function useProfessorForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    user_id:           null as number | null,
                    weekly_hour_limit: 20,
                }),
            }
        },
    }

    const updateOps = {
        form({ professor }: { professor: Professor }) {
            return {
                url:    update.url({ professor }),
                method: 'patch' as const,
                data:   useForm({
                    weekly_hour_limit: professor.weeklyHourLimit,
                    active:            professor.active,
                }),
            }
        },
    }

    const removeOps = {
        submit({ professor }: { professor: Professor }): void {
            useForm({}).delete(destroy.url({ professor }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
