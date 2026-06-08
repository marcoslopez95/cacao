import { describe, it, expect, beforeEach } from 'vitest'
import { ref } from 'vue'
import type { Ref } from 'vue'
import { useEnrollmentPermissions } from '@/composables/enrollment/useEnrollmentPermissions'
import type { BackendEnrollment } from '@/types/enrollment'

function makeEnrollment(
    status: BackendEnrollment['status'] = 'draft',
): BackendEnrollment {
    return {
        id: 1,
        student_id: 1,
        period: '2026-I',
        pensum: 'Pensum 2020',
        uc_disponibles: 20,
        uc_inscritas: 6,
        status,
        details: [],
    }
}

describe('useEnrollmentPermissions', () => {
    let enrollment: Ref<BackendEnrollment | null>

    beforeEach(() => {
        enrollment = ref<BackendEnrollment | null>(makeEnrollment('draft'))
    })

    // -------------------------------------------------------------------------
    // isReadOnly
    // -------------------------------------------------------------------------

    it('isReadOnly is false when enrollment is in draft status', () => {
        const { isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(isReadOnly.value).toBe(false)
    })

    it('isReadOnly is true when enrollment is in confirmed status', () => {
        enrollment.value = makeEnrollment('confirmed')
        const { isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(isReadOnly.value).toBe(true)
    })

    it('isReadOnly is true when enrollment is in approved status', () => {
        enrollment.value = makeEnrollment('approved')
        const { isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(isReadOnly.value).toBe(true)
    })

    it('isReadOnly is true when enrollment is in rejected status', () => {
        enrollment.value = makeEnrollment('rejected')
        const { isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(isReadOnly.value).toBe(true)
    })

    it('isReadOnly is true when enrollment is null', () => {
        enrollment.value = null
        const { isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(isReadOnly.value).toBe(true)
    })

    // -------------------------------------------------------------------------
    // canEdit
    // -------------------------------------------------------------------------

    it('canEdit is true when enrollment status is draft (student with draft enrollment)', () => {
        enrollment.value = makeEnrollment('draft')
        const { canEdit } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(canEdit.value).toBe(true)
    })

    it('canEdit is false when enrollment status is confirmed (professor/admin read-only view)', () => {
        enrollment.value = makeEnrollment('confirmed')
        const { canEdit } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(canEdit.value).toBe(false)
    })

    it('canEdit is false when enrollment status is approved', () => {
        enrollment.value = makeEnrollment('approved')
        const { canEdit } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(canEdit.value).toBe(false)
    })

    it('canEdit is false when enrollment status is rejected', () => {
        enrollment.value = makeEnrollment('rejected')
        const { canEdit } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(canEdit.value).toBe(false)
    })

    it('canEdit is false when enrollment is null', () => {
        enrollment.value = null
        const { canEdit } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canEdit.value).toBe(false)
    })

    // -------------------------------------------------------------------------
    // canConfirm — depends on BOTH can.confirm flag AND enrollment status
    // -------------------------------------------------------------------------

    it('canConfirm is true when can.confirm is true and enrollment is draft (student with permission)', () => {
        enrollment.value = makeEnrollment('draft')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canConfirm.value).toBe(true)
    })

    it('canConfirm is false when can.confirm is false even if enrollment is draft (admin/professor — no confirm perm)', () => {
        enrollment.value = makeEnrollment('draft')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: false })
        expect(canConfirm.value).toBe(false)
    })

    it('canConfirm is false when enrollment is already confirmed even with can.confirm=true (guardian read-only)', () => {
        enrollment.value = makeEnrollment('confirmed')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canConfirm.value).toBe(false)
    })

    it('canConfirm is false when enrollment is approved', () => {
        enrollment.value = makeEnrollment('approved')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canConfirm.value).toBe(false)
    })

    it('canConfirm is false when enrollment is rejected', () => {
        enrollment.value = makeEnrollment('rejected')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canConfirm.value).toBe(false)
    })

    it('canConfirm is false when enrollment is null', () => {
        enrollment.value = null
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })
        expect(canConfirm.value).toBe(false)
    })

    // -------------------------------------------------------------------------
    // Reactivity — permissions update when enrollment ref changes
    // -------------------------------------------------------------------------

    it('canEdit and isReadOnly update reactively when enrollment status changes', () => {
        enrollment.value = makeEnrollment('draft')
        const { canEdit, isReadOnly } = useEnrollmentPermissions(enrollment, { confirm: true })

        expect(canEdit.value).toBe(true)
        expect(isReadOnly.value).toBe(false)

        // Simulate enrollment being confirmed after student submits
        enrollment.value = makeEnrollment('confirmed')

        expect(canEdit.value).toBe(false)
        expect(isReadOnly.value).toBe(true)
    })

    it('canConfirm updates reactively when enrollment status changes from draft to confirmed', () => {
        enrollment.value = makeEnrollment('draft')
        const { canConfirm } = useEnrollmentPermissions(enrollment, { confirm: true })

        expect(canConfirm.value).toBe(true)

        enrollment.value = makeEnrollment('confirmed')

        expect(canConfirm.value).toBe(false)
    })
})
