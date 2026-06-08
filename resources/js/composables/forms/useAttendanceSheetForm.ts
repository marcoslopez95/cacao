import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { upsertAttendance } from '@/actions/App/Http/Controllers/Professor/AttendanceController'
import type { AttendanceMarks } from '@/types/attendance'

export function useAttendanceSheetForm(sectionId: number, sessionId: number) {
    const processing = ref(false)
    const saved = ref(false)
    const errors = ref<Record<string, string>>({})

    function save(marks: AttendanceMarks, professorPresent: boolean = true): void {
        processing.value = true
        router.put(
            upsertAttendance.url({ section: sectionId, classSession: sessionId }),
            { marks, professor_present: professorPresent },
            {
                onSuccess: () => {
                    processing.value = false
                    saved.value = true
                    setTimeout(() => {
 saved.value = false 
}, 3000)
                },
                onError: (e) => {
 processing.value = false; errors.value = e 
},
            }
        )
    }

    return { processing, saved, errors, save }
}
