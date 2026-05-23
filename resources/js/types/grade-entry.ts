import type { GradeSlot } from '@/types/grade-config'

export interface GradeEntry {
    id: number
    enrollment_detail_id: number
    grade_slot_id: number
    lapse_id: number | null
    parent_id: number | null
    name: string | null
    weight: string | null
    value: string | null
    is_published: boolean
    children?: GradeEntry[]
}

export interface GradeSheetStudent {
    enrollment_detail_id: number
    name: string
    entries_by_slot: Record<number, GradeEntry | null>
}

export interface SectionGradeSheet {
    section_id: number
    section_code: string
    subject_name: string
    lapse_id: number | null
    passing_value: number
    slots: GradeSlot[]
    students: GradeSheetStudent[]
}

export interface StudentGradeSlot {
    slot_id: number
    slot_name: string
    weight: string
    value: string | null
    is_published: boolean
    is_remedial: boolean
    children: Array<{
        name: string
        weight: string
        value: string | null
    }>
}

export interface StudentGradeSubject {
    enrollment_detail_id: number
    subject_name: string
    lapse_id: number | null
    slots: StudentGradeSlot[]
    final_grade: string | null
    passed: boolean | null
}

export interface StudentGradeCard {
    period: string
    subjects: StudentGradeSubject[]
}
