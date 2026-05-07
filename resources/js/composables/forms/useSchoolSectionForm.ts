// resources/js/composables/forms/useSchoolSectionForm.ts
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/sections/school'
import type { SchoolSection } from '@/types/scheduling'

export function useSchoolSectionForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    period_id:       null as number | null,
                    pensum_id:       null as number | null,
                    grade:           null as number | null,
                    letter:          '',
                    capacity:        30,
                    main_teacher_id: null as number | null,
                    classroom_id:    null as number | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ section }: { section: SchoolSection }) {
            return {
                url:    update.url({ section }),
                method: 'patch' as const,
                data:   useForm({
                    letter:          section.letter,
                    capacity:        section.capacity,
                    main_teacher_id: section.mainTeacher?.id ?? null,
                    classroom_id:    section.classroom?.id ?? null,
                }),
            }
        },
    }

    const removeOps = {
        submit({ section }: { section: SchoolSection }): void {
            useForm({}).delete(destroy.url({ section }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
