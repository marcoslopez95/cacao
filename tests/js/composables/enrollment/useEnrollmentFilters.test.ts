import { describe, it, expect } from 'vitest'
import { useEnrollmentFilters } from '@/composables/enrollment/useEnrollmentFilters'
import type { EnrollmentSubject, EnrollmentSection } from '@/types/enrollment'

const defaultSection: EnrollmentSection = {
    code: 'A',
    professor: { id: '1', name: 'Prof. Test', initials: 'PT' },
    room: 'A-101',
    modality: 'Teórica',
    capacity: 30,
    enrolled: 10,
    slots: [],
    noSchedule: false,
}

function makeSubject(overrides: Partial<EnrollmentSubject> = {}): EnrollmentSubject {
    return {
        id: 1,
        code: 'MAT101',
        name: 'Cálculo I',
        credits: 4,
        type: 'oblig',
        recommendedTrim: true,
        prereqsOk: true,
        completed: false,
        description: '',
        sections: [defaultSection],
        ...overrides,
    }
}

describe('useEnrollmentFilters', () => {
    it('initializes search as empty string', () => {
        const { search } = useEnrollmentFilters([makeSubject()])
        expect(search.value).toBe('')
    })

    it('initializes default filters with hideCompleted=true', () => {
        const { filters } = useEnrollmentFilters([makeSubject()])
        expect(filters.value.hideCompleted).toBe(true)
        expect(filters.value.type).toBe('all')
        expect(filters.value.recommendedOnly).toBe(false)
        expect(filters.value.prereqsOnly).toBe(false)
    })

    it('returns all non-completed subjects by default', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', completed: false }),
            makeSubject({ code: 'FIS101', completed: true }),
        ]
        const { filteredSubjects } = useEnrollmentFilters(subjects)
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('MAT101')
    })

    it('shows completed subjects when hideCompleted is false', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', completed: false }),
            makeSubject({ code: 'FIS101', completed: true }),
        ]
        const { filters, filteredSubjects } = useEnrollmentFilters(subjects)
        filters.value = { ...filters.value, hideCompleted: false }
        expect(filteredSubjects.value).toHaveLength(2)
    })

    it('filters by type when type is not "all"', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', type: 'oblig' }),
            makeSubject({ code: 'OPT101', type: 'electiva' }),
        ]
        const { filters, filteredSubjects } = useEnrollmentFilters(subjects)
        filters.value = { ...filters.value, type: 'electiva' }
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('OPT101')
    })

    it('filters by search term matching subject name (case-insensitive)', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', name: 'Cálculo I' }),
            makeSubject({ code: 'FIS101', name: 'Física I' }),
        ]
        const { search, filteredSubjects } = useEnrollmentFilters(subjects)
        search.value = 'cálculo'
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('MAT101')
    })

    it('filters by search term matching subject code', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', name: 'Cálculo I' }),
            makeSubject({ code: 'FIS101', name: 'Física I' }),
        ]
        const { search, filteredSubjects } = useEnrollmentFilters(subjects)
        search.value = 'FIS'
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('FIS101')
    })

    it('filters to only recommended subjects when recommendedOnly is true', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', recommendedTrim: true }),
            makeSubject({ code: 'MAT201', recommendedTrim: false }),
        ]
        const { filters, filteredSubjects } = useEnrollmentFilters(subjects)
        filters.value = { ...filters.value, recommendedOnly: true }
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('MAT101')
    })

    it('filters to only subjects with prereqs met when prereqsOnly is true', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', prereqsOk: true }),
            makeSubject({ code: 'MAT301', prereqsOk: false }),
        ]
        const { filters, filteredSubjects } = useEnrollmentFilters(subjects)
        filters.value = { ...filters.value, prereqsOnly: true }
        expect(filteredSubjects.value).toHaveLength(1)
        expect(filteredSubjects.value[0].code).toBe('MAT101')
    })

    it('returns empty array when no subjects match combined filters', () => {
        const subjects = [
            makeSubject({ code: 'MAT101', type: 'oblig', completed: false }),
        ]
        const { search, filters, filteredSubjects } = useEnrollmentFilters(subjects)
        filters.value = { ...filters.value, type: 'electiva' }
        search.value = 'MAT'
        expect(filteredSubjects.value).toHaveLength(0)
    })
})
