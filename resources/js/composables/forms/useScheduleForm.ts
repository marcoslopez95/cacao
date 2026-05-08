import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/schedules'
import type { Schedule } from '@/types/scheduling'

export function useScheduleForm() {
    const storeOps = {
        form(defaults: { sectionId?: number; dayOfWeek?: string; startTime?: string } = {}) {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    section_id:   (defaults.sectionId ?? null) as number | null,
                    professor_id: null as number | null,
                    classroom_id: null as number | null,
                    subject_id:   null as number | null,
                    day_of_week:  defaults.dayOfWeek ?? 'monday',
                    start_time:   defaults.startTime ?? '08:00',
                    end_time:     '08:45',
                    type:         'theory' as 'theory' | 'lab',
                    valid_from:   '',
                    valid_until:  null as string | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ schedule }: { schedule: Schedule }) {
            return {
                url:    update.url({ schedule }),
                method: 'patch' as const,
                data:   useForm({
                    section_id:   schedule.section.id,
                    professor_id: schedule.professor.id,
                    classroom_id: schedule.classroom.id,
                    subject_id:   schedule.subject.id,
                    day_of_week:  schedule.dayOfWeek,
                    start_time:   schedule.startTime,
                    end_time:     schedule.endTime,
                    type:         schedule.type,
                    valid_from:   schedule.validFrom,
                    valid_until:  schedule.validUntil,
                }),
            }
        },
    }

    const removeOps = {
        submit({ schedule }: { schedule: Schedule }): void {
            useForm({}).delete(destroy.url({ schedule }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
