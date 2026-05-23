import type { PaginationMeta } from './pagination'

export type EnrollmentDisplayStatus = 'confirmed' | 'draft' | 'approved' | 'rejected' | null

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
    gpa: null
    cedula: null
    code: null
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
}
