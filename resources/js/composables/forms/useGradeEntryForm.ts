import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { publish as publishSlot } from '@/routes/professor/grades'
import { upsert as upsertEntry } from '@/routes/professor/grades/entries'
import { store as enableRemedialRoute } from '@/routes/professor/grades/remedial'
import type { GradeEntry } from '@/types/grade-entry'

type SaveState = 'idle' | 'saving' | 'saved' | 'error'

export function useGradeEntryForm(sectionId: number) {
    const saveStates = ref<Record<string, SaveState>>({})

    function cellKey(enrollmentDetailId: number, slotId: number, lapseId: number | null): string {
        return `${enrollmentDetailId}-${slotId}-${lapseId ?? 0}`
    }

    async function upsertEntryFn(payload: {
        enrollment_detail_id: number
        grade_slot_id: number
        lapse_id: number | null
        parent_id: number | null
        name?: string | null
        weight?: number | null
        value: number | null
    }): Promise<GradeEntry | null> {
        const key = cellKey(payload.enrollment_detail_id, payload.grade_slot_id, payload.lapse_id)
        saveStates.value[key] = 'saving'

        return new Promise((resolve) => {
            router.put(
                upsertEntry.url({ section: sectionId }),
                payload as Record<string, unknown>,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        saveStates.value[key] = 'saved'
                        setTimeout(() => {
 saveStates.value[key] = 'idle' 
}, 1500)
                        resolve(null)
                    },
                    onError: () => {
                        saveStates.value[key] = 'error'
                        resolve(null)
                    },
                }
            )
        })
    }

    function publishSlotFn(slotId: number, lapseId: number | null): void {
        router.post(
            publishSlot.url({ section: sectionId }),
            { grade_slot_id: slotId, lapse_id: lapseId },
            { preserveScroll: true }
        )
    }

    function enableRemedialFn(enrollmentDetailId: number): void {
        router.post(
            enableRemedialRoute.url({ section: sectionId, enrollmentDetail: enrollmentDetailId }),
            {},
            { preserveScroll: true }
        )
    }

    return { saveStates, cellKey, upsertEntry: upsertEntryFn, publishSlot: publishSlotFn, enableRemedial: enableRemedialFn }
}
