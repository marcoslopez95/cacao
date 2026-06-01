import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { upsertAttendance } from '@/actions/App/Http/Controllers/Admin/AttendanceController'
import type { AttendanceMarks } from '@/types/attendance'

export function useAdminAttendanceSheetForm(sectionId: number, sessionId: number) {
    const processing = ref(false)
    const saved = ref(false)
    const errors = ref<Record<string, string>>({})

    /**
     * Save attendance via admin endpoint.
     * professor_present is always forced to false server-side, but we send it anyway.
     */
    function save(marks: AttendanceMarks): void {
        processing.value = true
        router.put(
            upsertAttendance.url({ section: sectionId, classSession: sessionId }),
            { marks },
            {
                onSuccess: () => {
                    processing.value = false
                    saved.value = true
                    setTimeout(() => { saved.value = false }, 3000)
                },
                onError: (e) => { processing.value = false; errors.value = e },
            }
        )
    }

    return { processing, saved, errors, save }
}
