import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/infrastructure/classrooms'
import type { Classroom } from '@/types/infrastructure'

export function useClassroomForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    building_id: null as number | null,
                    identifier:  '',
                    type:        'theory' as 'theory' | 'laboratory',
                    capacity:    30,
                }),
            }
        },
    }

    const updateOps = {
        form({ classroom }: { classroom: Classroom }) {
            return {
                url:    update.url({ classroom }),
                method: 'patch' as const,
                data:   useForm({
                    building_id: classroom.building.id,
                    identifier:  classroom.identifier,
                    type:        classroom.type,
                    capacity:    classroom.capacity,
                }),
            }
        },
    }

    const removeOps = {
        submit({ classroom }: { classroom: Classroom }): void {
            useForm({}).delete(destroy.url({ classroom }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
