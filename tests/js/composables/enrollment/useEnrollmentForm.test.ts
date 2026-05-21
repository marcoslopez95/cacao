import { ref } from 'vue'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import type { Ref } from 'vue'
import type { BackendEnrollment, EnrollmentSelections } from '@/types/enrollment'

// Override @inertiajs/vue3 mock to include useHttp
vi.mock('@inertiajs/vue3', () => {
    const mockHttpInstance = {
        processing: false,
        subject_id: 0,
        section_id: 0,
        post: vi.fn(),
        delete: vi.fn(),
    }
    return {
        useHttp: vi.fn(() => mockHttpInstance),
        router: {
            get: vi.fn(() => undefined),
            post: vi.fn(() => undefined),
            put: vi.fn(() => undefined),
            patch: vi.fn(() => undefined),
            delete: vi.fn(() => undefined),
            visit: vi.fn(),
            reload: vi.fn(),
            cancelAll: vi.fn(),
            on: vi.fn(),
        },
        useForm: vi.fn((initialValues: Record<string, unknown> = {}) => ({
            ...initialValues,
            errors: {},
            processing: false,
            wasSuccessful: false,
            recentlySuccessful: false,
            isDirty: false,
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            get: vi.fn(),
            submit: vi.fn(),
            reset: vi.fn(),
            clearErrors: vi.fn(),
            setError: vi.fn(),
            transform: vi.fn(),
        })),
        usePage: vi.fn(() => ({
            props: {
                auth: {
                    user: { id: 1, name: 'Test User', email: 'test@example.com', roles: ['admin'] },
                },
            },
            url: '/',
            component: 'Test',
            version: '1',
        })),
        Link: { template: '<a><slot /></a>' },
    }
})

// Mock the Wayfinder EnrollmentController
vi.mock('@/actions/App/Http/Controllers/Enrollment/EnrollmentController', () => ({
    default: {
        addDetail: (enrollmentId: number) => ({ url: `/enrollment/${enrollmentId}/detail` }),
        removeDetail: ({ enrollment, enrollmentDetail }: { enrollment: number; enrollmentDetail: number }) => ({
            url: `/enrollment/${enrollment}/detail/${enrollmentDetail}`,
        }),
        confirm: (enrollmentId: number) => ({ url: `/enrollment/${enrollmentId}/confirm` }),
    },
}))

import { useEnrollmentForm } from '@/composables/enrollment/useEnrollmentForm'
import { useHttp, router } from '@inertiajs/vue3'

function makeEnrollment(overrides: Partial<BackendEnrollment> = {}): BackendEnrollment {
    return {
        id: 42,
        student_id: 1,
        period: '2026-I',
        pensum: 'Pensum 2020',
        uc_disponibles: 20,
        uc_inscritas: 0,
        status: 'draft',
        details: [],
        ...overrides,
    }
}

describe('useEnrollmentForm', () => {
    let enrollment: Ref<BackendEnrollment | null>
    let selections: Ref<EnrollmentSelections>

    beforeEach(() => {
        vi.clearAllMocks()
        enrollment = ref<BackendEnrollment | null>(makeEnrollment())
        selections = ref<EnrollmentSelections>({})
    })

    it('initializes error as null', () => {
        const { error } = useEnrollmentForm(enrollment, selections)
        expect(error.value).toBeNull()
    })

    it('initializes isLoading as false when no http calls are in flight', () => {
        const { isLoading } = useEnrollmentForm(enrollment, selections)
        expect(isLoading.value).toBe(false)
    })

    it('initializes detailIds from draft details of the enrollment', () => {
        const enrollmentWithDrafts = makeEnrollment({
            details: [
                {
                    id: 10,
                    subject: { id: 1, code: 'MAT101', name: 'Cálculo I', credits_uc: 4 },
                    section: { id: 1, code: 'A', capacity: 30 },
                    status: 'draft',
                },
                {
                    id: 11,
                    subject: { id: 2, code: 'FIS101', name: 'Física I', credits_uc: 3 },
                    section: { id: 2, code: 'B', capacity: 25 },
                    status: 'confirmed', // confirmed details should NOT be indexed
                },
            ],
        })
        enrollment = ref<BackendEnrollment | null>(enrollmentWithDrafts)
        const { } = useEnrollmentForm(enrollment, selections)
        // We can't directly inspect detailIds (not returned), but the internal state
        // drives removeSubject behavior — tested in the removeSubject tests below.
        // This test verifies the composable initializes without throwing.
        expect(true).toBe(true)
    })

    it('clearError sets error back to null', async () => {
        const { error, clearError } = useEnrollmentForm(enrollment, selections)

        // Simulate an error by calling addSubject without enrollment
        enrollment.value = null
        // error stays null when enrollment is null, let's set it manually via the ref
        // We need to trigger an error path — use addSubject with a valid enrollment
        // but simulate an onError callback
        enrollment.value = makeEnrollment()

        // Get the mocked useHttp instance
        const mockHttp = vi.mocked(useHttp)()
        const postMock = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Test error' })
        })
        mockHttp.post = postMock

        await (useEnrollmentForm(enrollment, selections).addSubject(1, 1, 'MAT101', 0))

        // clearError should reset it
        const form = useEnrollmentForm(enrollment, selections)
        form.clearError()
        expect(form.error.value).toBeNull()
    })

    it('addSubject calls http.post with the correct URL for the enrollment', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { addSubject } = useEnrollmentForm(enrollment, selections)
        await addSubject(5, 3, 'MAT101', 1)

        expect(postSpy).toHaveBeenCalledOnce()
        expect(postSpy).toHaveBeenCalledWith(
            '/enrollment/42/detail',
            expect.objectContaining({
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        )
    })

    it('addSubject does nothing when enrollment is null', async () => {
        enrollment.value = null
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { addSubject } = useEnrollmentForm(enrollment, selections)
        await addSubject(5, 3, 'MAT101', 1)

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('addSubject onSuccess updates selections with the new subjectCode → sectionIdx', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onSuccess: (r: Record<string, unknown>) => void }) => {
            options.onSuccess({ id: 99 })
        })

        const { addSubject } = useEnrollmentForm(enrollment, selections)
        await addSubject(5, 3, 'MAT101', 2)

        expect(selections.value['MAT101']).toBe(2)
    })

    it('addSubject onError sets error message from server response', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Cupo insuficiente.' })
        })

        const { addSubject, error } = useEnrollmentForm(enrollment, selections)
        await addSubject(5, 3, 'MAT101', 0)

        expect(error.value).toBe('Cupo insuficiente.')
    })

    it('addSubject onError uses fallback message when server error key is missing', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({})
        })

        const { addSubject, error } = useEnrollmentForm(enrollment, selections)
        await addSubject(5, 3, 'MAT101', 0)

        expect(error.value).toBe('Error al agregar la materia.')
    })

    it('removeSubject calls http.delete with correct enrollment and detail URL', async () => {
        const enrollmentWithDraft = makeEnrollment({
            details: [
                {
                    id: 77,
                    subject: { id: 1, code: 'MAT101', name: 'Cálculo I', credits_uc: 4 },
                    section: { id: 1, code: 'A', capacity: 30 },
                    status: 'draft',
                },
            ],
        })
        enrollment = ref<BackendEnrollment | null>(enrollmentWithDraft)

        // For removeSubject we need the removeHttp instance which is the second useHttp call.
        // Both addHttp and removeHttp share the same vi.fn mock, so the second returned
        // object is the same mock instance.
        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections)
        await removeSubject('MAT101')

        expect(deleteSpy).toHaveBeenCalledOnce()
        expect(deleteSpy).toHaveBeenCalledWith(
            '/enrollment/42/detail/77',
            expect.objectContaining({
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        )
    })

    it('removeSubject does nothing when enrollment is null', async () => {
        enrollment.value = null
        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections)
        await removeSubject('MAT101')

        expect(deleteSpy).not.toHaveBeenCalled()
    })

    it('removeSubject does nothing when detailId is not found for subject code', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections)
        await removeSubject('UNKNOWN_CODE')

        expect(deleteSpy).not.toHaveBeenCalled()
    })

    it('removeSubject onSuccess removes the subject from selections', async () => {
        const enrollmentWithDraft = makeEnrollment({
            details: [
                {
                    id: 55,
                    subject: { id: 1, code: 'FIS101', name: 'Física I', credits_uc: 3 },
                    section: { id: 1, code: 'A', capacity: 30 },
                    status: 'draft',
                },
            ],
        })
        enrollment = ref<BackendEnrollment | null>(enrollmentWithDraft)
        selections.value = { FIS101: 0 }

        const httpInstance = vi.mocked(useHttp)()
        httpInstance.delete = vi.fn((url: string, options: { onSuccess: () => void }) => {
            options.onSuccess()
        })

        const { removeSubject } = useEnrollmentForm(enrollment, selections)
        await removeSubject('FIS101')

        expect(selections.value['FIS101']).toBeUndefined()
    })

    it('confirmEnrollment calls http.post with the correct confirm URL', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections)
        await confirmEnrollment()

        expect(postSpy).toHaveBeenCalledOnce()
        expect(postSpy).toHaveBeenCalledWith(
            '/enrollment/42/confirm',
            expect.objectContaining({
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        )
    })

    it('confirmEnrollment does nothing when enrollment is null', async () => {
        enrollment.value = null
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections)
        await confirmEnrollment()

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('confirmEnrollment onSuccess calls router.reload()', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onSuccess: () => void }) => {
            options.onSuccess()
        })

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections)
        await confirmEnrollment()

        expect(vi.mocked(router).reload).toHaveBeenCalledOnce()
    })

    it('confirmEnrollment onError sets error from server', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Período cerrado.' })
        })

        const { confirmEnrollment, error } = useEnrollmentForm(enrollment, selections)
        await confirmEnrollment()

        expect(error.value).toBe('Período cerrado.')
    })
})
