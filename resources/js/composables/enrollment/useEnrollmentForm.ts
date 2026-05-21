import { ref, computed } from 'vue'
import type { Ref, ComputedRef } from 'vue'
import { useHttp, router } from '@inertiajs/vue3'
import EnrollmentController from '@/actions/App/Http/Controllers/Enrollment/EnrollmentController'
import type { BackendEnrollment, EnrollmentSelections } from '@/types/enrollment'

export function useEnrollmentForm(
    enrollment: Ref<BackendEnrollment | null>,
    selections: Ref<EnrollmentSelections>,
) {
    const error = ref<string | null>(null)
    const detailIds = ref<Record<string, number>>(
        Object.fromEntries(
            (enrollment.value?.details ?? [])
                .filter(d => d.status === 'draft')
                .map(d => [d.subject.code, d.id])
        )
    )

    const addHttp = useHttp({ subject_id: 0, section_id: 0 })
    const removeHttp = useHttp({})
    const confirmHttp = useHttp({})

    const isLoading: ComputedRef<boolean> = computed(
        () => addHttp.processing || removeHttp.processing || confirmHttp.processing
    )

    function clearError(): void {
        error.value = null
    }

    async function addSubject(
        subjectId: number,
        sectionId: number,
        subjectCode: string,
        sectionIdx: number,
    ): Promise<void> {
        if (! enrollment.value) {
            return
        }

        error.value = null
        addHttp.subject_id = subjectId
        addHttp.section_id = sectionId

        addHttp.post(EnrollmentController.addDetail(enrollment.value.id).url, {
            onSuccess: (response: Record<string, unknown>) => {
                selections.value = { ...selections.value, [subjectCode]: sectionIdx }
                if (typeof response.id === 'number') {
                    detailIds.value[subjectCode] = response.id
                }
            },
            onError: (errors: Record<string, string>) => {
                error.value = errors.error ?? 'Error al agregar la materia.'
            },
        })
    }

    async function removeSubject(subjectCode: string): Promise<void> {
        if (! enrollment.value) {
            return
        }

        const detailId = detailIds.value[subjectCode]
        if (! detailId) {
            return
        }

        error.value = null

        removeHttp.delete(
            EnrollmentController.removeDetail({
                enrollment: enrollment.value.id,
                enrollmentDetail: detailId,
            }).url,
            {
                onSuccess: () => {
                    const nextSel = { ...selections.value }
                    delete nextSel[subjectCode]
                    selections.value = nextSel
                    const nextIds = { ...detailIds.value }
                    delete nextIds[subjectCode]
                    detailIds.value = nextIds
                },
                onError: (errors: Record<string, string>) => {
                    error.value = errors.error ?? 'Error al eliminar la materia.'
                },
            },
        )
    }

    async function confirmEnrollment(): Promise<void> {
        if (! enrollment.value) {
            return
        }

        error.value = null

        confirmHttp.post(EnrollmentController.confirm(enrollment.value.id).url, {
            onSuccess: () => {
                router.reload()
            },
            onError: (errors: Record<string, string>) => {
                error.value = errors.error ?? 'Error al confirmar la inscripción.'
            },
        })
    }

    return {
        isLoading,
        error,
        clearError,
        addSubject,
        removeSubject,
        confirmEnrollment,
    }
}
