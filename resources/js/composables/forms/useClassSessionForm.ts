import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { storeSession } from '@/actions/App/Http/Controllers/Professor/AttendanceController'

export function useClassSessionForm(sectionId: number) {
    const processing = ref(false)
    const errors = ref<Record<string, string>>({})

    function create(data: {
        type: 'regular' | 'makeup' | 'advance'
        linked_session_id?: number | null
        topic?: string | null
        held_at?: string | null
    }): void {
        processing.value = true
        router.post(
            storeSession.url({ section: sectionId }),
            data,
            {
                onSuccess: () => { processing.value = false; errors.value = {} },
                onError: (e) => { processing.value = false; errors.value = e },
            }
        )
    }

    return { processing, errors, create }
}
