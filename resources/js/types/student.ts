import type { PaginationMeta } from './pagination'

export type EnrollmentDisplayStatus = 'confirmed' | 'draft' | 'approved' | 'rejected' | null

export type StudentLevel = 'all' | 'primary' | 'secondary' | 'university'

export interface StudentListItem {
    id: number
    name: string
    email: string
    career_name: string | null
    career_id: number | null
    academic_year: number | null
    educational_level: 'university' | 'primary' | 'secondary'
    enrollment_status: EnrollmentDisplayStatus
    uc_inscritas: number
    gpa: number | null
    cedula: string | null
    code: string | null
    guardian_name: string | null
    guardian_relation: string | null
    section_grade: number | null
    section_letter: string | null
}

export interface StudentCollection {
    data: StudentListItem[]
    meta: PaginationMeta
}

export interface StudentFilters {
    search?: string
    career_id?: number[]
    academic_year?: number[]
    enrollment_status?: string[]
    level?: StudentLevel
    section_letter?: string[]
}
