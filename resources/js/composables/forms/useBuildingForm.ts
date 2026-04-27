import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/infrastructure/buildings'
import type { Building } from '@/types/infrastructure'

export function useBuildingForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({ name: '' }),
            }
        },
    }

    const updateOps = {
        form({ building }: { building: Building }) {
            return {
                url:    update.url({ building }),
                method: 'patch' as const,
                data:   useForm({ name: building.name }),
            }
        },
    }

    const removeOps = {
        submit({ building }: { building: Building }): void {
            useForm({}).delete(destroy.url({ building }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
