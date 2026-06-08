import { describe, it, expect } from 'vitest'
import { ref } from 'vue'
import { useEnrollmentState } from '@/composables/enrollment/useEnrollmentState'
import type { EnrollmentSubject, EnrollmentSection, EnrollmentSelections } from '@/types/enrollment'

const noSlotSection: EnrollmentSection = {
    id: 1,
    code: 'A',
    professor: { id: '1', name: 'Prof. Test', initials: 'PT' },
    room: 'A-101',
    modality: 'Teórica',
    capacity: 30,
    enrolled: 10,
    slots: [],
    noSchedule: true,
}

const mondaySection: EnrollmentSection = {
    id: 2,
    code: 'B',
    professor: { id: '2', name: 'Prof. B', initials: 'PB' },
    room: 'B-202',
    modality: 'Teórica',
    capacity: 25,
    enrolled: 5,
    slots: [{ day: 0, start: '08:00', end: '10:00' }],
    noSchedule: false,
}

const conflictSection: EnrollmentSection = {
    id: 3,
    code: 'C',
    professor: { id: '3', name: 'Prof. C', initials: 'PC' },
    room: 'C-303',
    modality: 'Teórica',
    capacity: 20,
    enrolled: 3,
    slots: [{ day: 0, start: '09:00', end: '11:00' }], // overlaps monday 08:00-10:00
    noSchedule: false,
}

const mat101: EnrollmentSubject = {
    id: 1,
    code: 'MAT101',
    name: 'Cálculo I',
    credits: 4,
    type: 'oblig',
    recommendedTrim: true,
    prereqsOk: true,
    completed: false,
    description: '',
    sections: [mondaySection],
}

const fis101: EnrollmentSubject = {
    id: 2,
    code: 'FIS101',
    name: 'Física I',
    credits: 3,
    type: 'oblig',
    recommendedTrim: true,
    prereqsOk: true,
    completed: false,
    description: '',
    sections: [conflictSection],
}

const pending101: EnrollmentSubject = {
    id: 3,
    code: 'PEN101',
    name: 'Sin Horario',
    credits: 2,
    type: 'oblig',
    recommendedTrim: false,
    prereqsOk: true,
    completed: false,
    description: '',
    sections: [noSlotSection],
}

describe('useEnrollmentState — summary', () => {
    it('returns zero-state summary when selections is empty', () => {
        const selections = ref<EnrollmentSelections>({})
        const { summary } = useEnrollmentState([mat101], selections, () => {})
        expect(summary.value.credits).toBe(0)
        expect(summary.value.count).toBe(0)
        expect(summary.value.items).toHaveLength(0)
    })

    it('accumulates credits for selected subjects', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0, FIS101: 0 })
        const { summary } = useEnrollmentState([mat101, fis101], selections, () => {})
        expect(summary.value.credits).toBe(7) // 4 + 3
    })

    it('counts scheduled sections separately from pending ones', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0, PEN101: 0 })
        const { summary } = useEnrollmentState([mat101, pending101], selections, () => {})
        expect(summary.value.scheduled).toBe(1)
        expect(summary.value.pending).toBe(1)
    })

    it('includes summary items for each selected subject', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        const { summary } = useEnrollmentState([mat101], selections, () => {})
        expect(summary.value.items).toHaveLength(1)
        expect(summary.value.items[0].subject.code).toBe('MAT101')
        expect(summary.value.items[0].sectionIdx).toBe(0)
    })

    it('ignores unknown subject codes in selections', () => {
        const selections = ref<EnrollmentSelections>({ UNKNOWN: 0 })
        const { summary } = useEnrollmentState([mat101], selections, () => {})
        expect(summary.value.credits).toBe(0)
        expect(summary.value.count).toBe(0)
    })
})

describe('useEnrollmentState — creditsStatus', () => {
    it('returns "low" when credits are below 12', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 }) // 4 credits
        const { creditsStatus } = useEnrollmentState([mat101], selections, () => {})
        expect(creditsStatus.value).toBe('low')
    })

    it('returns "ok" when credits are between 12 and 24 (inclusive)', () => {
        const heavySubjects: EnrollmentSubject[] = Array.from({ length: 3 }, (_, i) => ({
            id: i + 1,
            code: `SUB${i}`,
            name: `Subject ${i}`,
            credits: 5,
            type: 'oblig' as const,
            recommendedTrim: true,
            prereqsOk: true,
            completed: false,
            description: '',
            sections: [{ ...noSlotSection, id: i + 10, code: String(i) }],
        }))
        const selectionMap: EnrollmentSelections = {}
        heavySubjects.forEach(s => {
 selectionMap[s.code] = 0 
})
        const selections = ref<EnrollmentSelections>(selectionMap) // 15 credits total
        const { creditsStatus } = useEnrollmentState(heavySubjects, selections, () => {})
        expect(creditsStatus.value).toBe('ok')
    })
})

describe('useEnrollmentState — creditsPct', () => {
    it('returns 0 when no subjects are selected', () => {
        const selections = ref<EnrollmentSelections>({})
        const { creditsPct } = useEnrollmentState([mat101], selections, () => {})
        expect(creditsPct.value).toBe(0)
    })

    it('caps at 100 when credits exceed 24', () => {
        const tooManySubjects: EnrollmentSubject[] = Array.from({ length: 7 }, (_, i) => ({
            id: i + 1,
            code: `BIG${i}`,
            name: `Big Subject ${i}`,
            credits: 4,
            type: 'oblig' as const,
            recommendedTrim: true,
            prereqsOk: true,
            completed: false,
            description: '',
            sections: [{ ...noSlotSection, id: i + 20, code: String(i) }],
        }))
        const selectionMap: EnrollmentSelections = {}
        tooManySubjects.forEach(s => {
 selectionMap[s.code] = 0 
})
        const selections = ref<EnrollmentSelections>(selectionMap) // 28 credits
        const { creditsPct } = useEnrollmentState(tooManySubjects, selections, () => {})
        expect(creditsPct.value).toBe(100)
    })
})

describe('useEnrollmentState — findConflict', () => {
    it('returns null when the candidate section has no slots', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        const { findConflict } = useEnrollmentState([mat101], selections, () => {})
        expect(findConflict(noSlotSection, 'FIS101')).toBeNull()
    })

    it('returns null when no selected subjects have overlapping slots', () => {
        const afternoonSection: EnrollmentSection = {
            ...mondaySection,
            id: 99,
            slots: [{ day: 0, start: '14:00', end: '16:00' }],
        }
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        const { findConflict } = useEnrollmentState([mat101], selections, () => {})
        // afternoon section doesn't overlap 08:00-10:00
        expect(findConflict(afternoonSection, 'FIS101')).toBeNull()
    })

    it('detects conflict when candidate slots overlap with a selected section', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        const { findConflict } = useEnrollmentState([mat101], selections, () => {})
        // conflictSection (09:00-11:00) overlaps mondaySection (08:00-10:00)
        const conflict = findConflict(conflictSection, 'FIS101')
        expect(conflict).not.toBeNull()
        expect(conflict?.subject.code).toBe('MAT101')
    })

    it('skips own subject code when checking for conflicts', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        const { findConflict } = useEnrollmentState([mat101], selections, () => {})
        // Checking MAT101 against itself — should not self-conflict
        expect(findConflict(mondaySection, 'MAT101')).toBeNull()
    })
})

describe('useEnrollmentState — select / unselect', () => {
    it('select calls setSelections with updated map', () => {
        const selections = ref<EnrollmentSelections>({})
        let capturedFn: ((prev: EnrollmentSelections) => EnrollmentSelections) | null = null
        const { select } = useEnrollmentState([mat101], selections, fn => {
 capturedFn = fn 
})
        select('MAT101', 0)
        expect(capturedFn).not.toBeNull()
        const result = capturedFn!({})
        expect(result['MAT101']).toBe(0)
    })

    it('unselect calls setSelections and removes the key', () => {
        const selections = ref<EnrollmentSelections>({ MAT101: 0 })
        let capturedFn: ((prev: EnrollmentSelections) => EnrollmentSelections) | null = null
        const { unselect } = useEnrollmentState([mat101], selections, fn => {
 capturedFn = fn 
})
        unselect('MAT101')
        expect(capturedFn).not.toBeNull()
        const result = capturedFn!({ MAT101: 0 })
        expect(result['MAT101']).toBeUndefined()
    })
})
