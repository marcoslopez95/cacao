import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/sections/university'
import type { UniversitySection } from '@/types/scheduling'

export function useUniversitySectionForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    period_id:           null as number | null,
                    subject_id:          null as number | null,
                    code:                '',
                    capacity:            30,
                    theory_classroom_id: null as number | null,
                    lab_classroom_id:    null as number | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ section }: { section: UniversitySection }) {
            return {
                url:    update.url({ section }),
                method: 'patch' as const,
                data:   useForm({
                    code:                section.code,
                    capacity:            section.capacity,
                    theory_classroom_id: section.theoryClassroom?.id ?? null,
                    lab_classroom_id:    section.labClassroom?.id ?? null,
                }),
            }
        },
    }

    const removeOps = {
        submit({ section }: { section: UniversitySection }): void {
            useForm({}).delete(destroy.url({ section }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
