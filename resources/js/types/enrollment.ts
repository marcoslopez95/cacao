// ---------------------------------------------------------------------------
// Backend API shapes (as returned by EnrollmentCatalogSubjectResource et al.)
// ---------------------------------------------------------------------------

export interface BackendEnrollmentSlot {
    day: 0 | 1 | 2 | 3 | 4 | 5
    start: string
    end: string
}

export interface BackendEnrollmentProfessor {
    id: number
    name: string
    initials: string
}

export interface BackendEnrollmentSection {
    id: number
    code: string
    capacity: number
    enrolled: number
    professor: BackendEnrollmentProfessor
    room: string
    modality: 'Teórica' | 'Práctica' | 'Mixta' | 'Laboratorio' | 'Por definir'
    slots: BackendEnrollmentSlot[]
    noSchedule: boolean
    isSelected: boolean
}

export interface BackendEnrollmentSubject {
    id: number
    code: string
    name: string
    credits: number
    type: 'oblig' | 'electiva'
    recommended_trim: boolean
    prereqs_ok: boolean
    completed: boolean
    description: string
    selected_section_id: number | null
    selected_detail_id: number | null
    sections: BackendEnrollmentSection[]
}

export interface BackendEnrollmentRules {
    period: string | null
    deadline: string | null
    days_left: number
    credits_min: number
    credits_max: number
    student_name: string
    student_code: string
    career: string
    trimester: string
}

export interface BackendEnrollmentDetail {
    id: number
    subject: { id: number; code: string; name: string; credits_uc: number }
    section: { id: number; code: string; capacity: number }
    status: 'draft' | 'confirmed' | 'rejected'
}

export interface BackendEnrollment {
    id: number
    student_id: number
    period: string
    pensum: string
    uc_disponibles: number
    uc_inscritas: number
    status: 'draft' | 'confirmed' | 'approved' | 'rejected'
    details: BackendEnrollmentDetail[]
}

// ---------------------------------------------------------------------------
// Mapping: backend → UI types
// ---------------------------------------------------------------------------

export function backendToCatalog(items: BackendEnrollmentSubject[]): EnrollmentSubject[] {
    return items.map(item => ({
        id: item.id,
        code: item.code,
        name: item.name,
        credits: item.credits,
        type: item.type,
        recommendedTrim: item.recommended_trim,
        prereqsOk: item.prereqs_ok,
        completed: item.completed,
        description: item.description,
        sections: item.sections.map(s => ({
            id: s.id,
            code: s.code,
            professor: s.professor,
            room: s.room,
            modality: s.modality,
            capacity: s.capacity,
            enrolled: s.enrolled,
            slots: s.slots,
            noSchedule: s.noSchedule,
        })),
    }))
}

// ---------------------------------------------------------------------------
// UI types (existing — used by components)
// ---------------------------------------------------------------------------

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
    id?: number
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
    id?: number
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
