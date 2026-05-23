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
    let isReadOnly: Ref<boolean>

    beforeEach(() => {
        vi.clearAllMocks()
        enrollment = ref<BackendEnrollment | null>(makeEnrollment())
        selections = ref<EnrollmentSelections>({})
        isReadOnly = ref(false)
    })

    it('initializes error as null', () => {
        const { error } = useEnrollmentForm(enrollment, selections, isReadOnly)
        expect(error.value).toBeNull()
    })

    it('initializes isLoading as false when no http calls are in flight', () => {
        const { isLoading } = useEnrollmentForm(enrollment, selections, isReadOnly)
        expect(isLoading.value).toBe(false)
    })

    it('indexes draft and confirmed details on init; excludes rejected so removeSubject cannot delete them', async () => {
        const enrollmentWithDetails = makeEnrollment({
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
                    status: 'confirmed', // confirmed → still indexed so it can be managed
                },
                {
                    id: 12,
                    subject: { id: 3, code: 'QUI101', name: 'Química I', credits_uc: 3 },
                    section: { id: 3, code: 'C', capacity: 20 },
                    status: 'rejected', // rejected → must NOT be indexed
                },
            ],
        })
        enrollment = ref<BackendEnrollment | null>(enrollmentWithDetails)

        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)

        // MAT101 is draft → detailId 10 was indexed → delete is called
        await removeSubject('MAT101')
        expect(deleteSpy).toHaveBeenCalledOnce()
        expect(deleteSpy).toHaveBeenCalledWith(
            '/enrollment/42/detail/10',
            expect.objectContaining({ onSuccess: expect.any(Function), onError: expect.any(Function) }),
        )

        deleteSpy.mockClear()

        // FIS101 is confirmed → also indexed → delete is called
        await removeSubject('FIS101')
        expect(deleteSpy).toHaveBeenCalledOnce()
        expect(deleteSpy).toHaveBeenCalledWith(
            '/enrollment/42/detail/11',
            expect.objectContaining({ onSuccess: expect.any(Function), onError: expect.any(Function) }),
        )

        deleteSpy.mockClear()

        // QUI101 is rejected → NOT indexed → delete must NOT be called
        await removeSubject('QUI101')
        expect(deleteSpy).not.toHaveBeenCalled()
    })

    it('addSubject indexes the returned detail id so a subsequent removeSubject can use it', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        // First call: addSubject → post returns id 99
        httpInstance.post = vi.fn((url: string, options: { onSuccess: (r: Record<string, unknown>) => void }) => {
            options.onSuccess({ id: 99 })
        })
        httpInstance.delete = deleteSpy

        const { addSubject, removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(5, 3, 'MAT101', 2)

        // Now removeSubject should use detailId 99 that was stored on onSuccess
        await removeSubject('MAT101')
        expect(deleteSpy).toHaveBeenCalledOnce()
        expect(deleteSpy).toHaveBeenCalledWith(
            '/enrollment/42/detail/99',
            expect.objectContaining({ onSuccess: expect.any(Function), onError: expect.any(Function) }),
        )
    })

    it('addSubject sets error on http failure', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Fallo de red.' })
        })

        const { addSubject, error } = useEnrollmentForm(enrollment, selections, isReadOnly)
        expect(error.value).toBeNull()

        await addSubject(1, 1, 'MAT101', 0)

        expect(error.value).toBe('Fallo de red.')
    })

    it('clearError resets error to null after a failure', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Fallo de red.' })
        })

        const { addSubject, error, clearError } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(1, 1, 'MAT101', 0)

        // Precondition: error was set by the failure
        expect(error.value).toBe('Fallo de red.')

        clearError()

        expect(error.value).toBeNull()
    })

    it('addSubject calls http.post with the correct URL for the enrollment', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { addSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
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

        const { addSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(5, 3, 'MAT101', 1)

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('addSubject does nothing when isReadOnly is true', async () => {
        isReadOnly.value = true
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { addSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(5, 3, 'MAT101', 1)

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('addSubject onSuccess updates selections with the new subjectCode → sectionIdx', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onSuccess: (r: Record<string, unknown>) => void }) => {
            options.onSuccess({ id: 99 })
        })

        const { addSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(5, 3, 'MAT101', 2)

        expect(selections.value['MAT101']).toBe(2)
    })

    it('addSubject onError sets error message from server response', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Cupo insuficiente.' })
        })

        const { addSubject, error } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await addSubject(5, 3, 'MAT101', 0)

        expect(error.value).toBe('Cupo insuficiente.')
    })

    it('addSubject onError uses fallback message when server error key is missing', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({})
        })

        const { addSubject, error } = useEnrollmentForm(enrollment, selections, isReadOnly)
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

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
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

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await removeSubject('MAT101')

        expect(deleteSpy).not.toHaveBeenCalled()
    })

    it('removeSubject does nothing when isReadOnly is true', async () => {
        isReadOnly.value = true
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

        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await removeSubject('MAT101')

        expect(deleteSpy).not.toHaveBeenCalled()
    })

    it('removeSubject does nothing when detailId is not found for subject code', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const deleteSpy = vi.fn()
        httpInstance.delete = deleteSpy

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
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

        const { removeSubject } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await removeSubject('FIS101')

        expect(selections.value['FIS101']).toBeUndefined()
    })

    it('confirmEnrollment calls http.post with the correct confirm URL', async () => {
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections, isReadOnly)
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

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await confirmEnrollment()

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('confirmEnrollment does nothing when isReadOnly is true', async () => {
        isReadOnly.value = true
        const httpInstance = vi.mocked(useHttp)()
        const postSpy = vi.fn()
        httpInstance.post = postSpy

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await confirmEnrollment()

        expect(postSpy).not.toHaveBeenCalled()
    })

    it('confirmEnrollment onSuccess calls router.reload()', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onSuccess: () => void }) => {
            options.onSuccess()
        })

        const { confirmEnrollment } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await confirmEnrollment()

        expect(vi.mocked(router).reload).toHaveBeenCalledOnce()
    })

    it('confirmEnrollment onError sets error from server', async () => {
        const httpInstance = vi.mocked(useHttp)()
        httpInstance.post = vi.fn((url: string, options: { onError: (e: Record<string, string>) => void }) => {
            options.onError({ error: 'Período cerrado.' })
        })

        const { confirmEnrollment, error } = useEnrollmentForm(enrollment, selections, isReadOnly)
        await confirmEnrollment()

        expect(error.value).toBe('Período cerrado.')
    })
})
