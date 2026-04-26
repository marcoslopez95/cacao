import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/academic/subjects'
import { sync } from '@/routes/academic/subjects/prerequisites'
import type { Career, Pensum, Subject } from '@/types/academic'

export function useSubjectForm() {
    const storeOps = {
        form({ career, pensum }: { career: Career; pensum: Pensum }) {
            return {
                url:    store.url({ career, pensum }),
                method: 'post' as const,
                data:   useForm({
                    name:          '',
                    credits_uc:    1,
                    period_number: 1,
                    description:   null as string | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ career, pensum, subject }: { career: Career; pensum: Pensum; subject: Subject }) {
            return {
                url:    update.url({ career, pensum, subject }),
                method: 'patch' as const,
                data:   useForm({
                    name:          subject.name,
                    code:          subject.code,
                    credits_uc:    subject.creditsUc,
                    period_number: subject.periodNumber,
                    description:   subject.description,
                }),
            }
        },
    }

    const removeOps = {
        submit({ career, pensum, subject }: { career: Career; pensum: Pensum; subject: Subject }): void {
            useForm({}).delete(destroy.url({ career, pensum, subject }))
        },
    }

    const syncPrerequisitesOps = {
        submit({ career, pensum, subject, prerequisites }: {
            career:        Career
            pensum:        Pensum
            subject:       Subject
            prerequisites: number[]
        }): void {
            useForm({ prerequisites }).post(sync.url({ career, pensum, subject }))
        },
    }

    return {
        store:              storeOps,
        update:             updateOps,
        remove:             removeOps,
        syncPrerequisites:  syncPrerequisitesOps,
    }
}
