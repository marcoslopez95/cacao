export type EnrollmentDay = 0 | 1 | 2 | 3 | 4 | 5

export interface EnrollmentProfessor {
    id: string
    name: string
    initials: string
}

export interface EnrollmentSlot {
    day: EnrollmentDay
    start: string // "07:00"
    end: string   // "09:00"
}

export interface EnrollmentSection {
    code: string
    professor: EnrollmentProfessor
    room: string
    modality: 'Teórica' | 'Práctica' | 'Mixta' | 'Laboratorio' | 'Por definir'
    capacity: number
    enrolled: number
    slots: EnrollmentSlot[]
    noSchedule?: boolean
}

export interface EnrollmentSubject {
    code: string
    name: string
    credits: number
    type: 'oblig' | 'electiva'
    recommendedTrim: boolean
    prereqsOk: boolean
    completed: boolean
    description: string
    sections: EnrollmentSection[]
}

export interface EnrollmentRules {
    creditsMin: number
    creditsMax: number
    period: string
    studentName: string
    studentCode: string
    career: string
    trimester: string
    deadline: string
    daysLeft: number
}

export interface EnrollmentSummaryItem {
    subject: EnrollmentSubject
    section: EnrollmentSection
    sectionIdx: number
}

export interface EnrollmentSummary {
    credits: number
    hours: number
    scheduled: number
    pending: number
    count: number
    items: EnrollmentSummaryItem[]
}

export interface EnrollmentConflict {
    subject: EnrollmentSubject
    section: EnrollmentSection
    slotA: EnrollmentSlot
    slotB: EnrollmentSlot
}

export type EnrollmentSelections = Record<string, number>  // subjectCode → sectionIdx

export interface EnrollmentFilters {
    type: 'all' | 'oblig' | 'electiva'
    recommendedOnly: boolean
    prereqsOnly: boolean
    hideCompleted: boolean
}

export interface EnrollmentGhostCandidate {
    subjectCode: string
    section: EnrollmentSection
}
