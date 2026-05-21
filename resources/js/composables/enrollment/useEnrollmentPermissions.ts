import { computed } from 'vue'
import type { Ref, ComputedRef } from 'vue'
import type { BackendEnrollment } from '@/types/enrollment'

export function useEnrollmentPermissions(
    enrollment: Ref<BackendEnrollment | null>,
    can: { confirm: boolean },
) {
    const canConfirm: ComputedRef<boolean> = computed(
        () => can.confirm && enrollment.value?.status === 'draft',
    )

    const canEdit: ComputedRef<boolean> = computed(
        () => enrollment.value?.status === 'draft',
    )

    const isReadOnly: ComputedRef<boolean> = computed(
        () => ! enrollment.value || enrollment.value.status !== 'draft',
    )

    return { canConfirm, canEdit, isReadOnly }
}
