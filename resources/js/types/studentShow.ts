export interface GuardianSummary {
    id: number
    name: string
    email: string
    kinship: number | null
    primary: boolean
}

export interface ActiveEnrollment {
    id: number
    period_name: string
    status: 'draft' | 'confirmed' | 'approved' | 'rejected'
    uc_inscritas: number
    uc_disponibles: number
}

export interface EnrollmentHistoryItem {
    id: number
    period_name: string | null
    status: 'draft' | 'confirmed' | 'approved' | 'rejected' | null
    uc_inscritas: number
    uc_disponibles: number
}

export interface StudentShowData {
    id: number
    user_id: number

    // Identity
    name: string
    email: string
    cedula: string | null
    student_code: string | null

    // Education level
    educational_level: 'university' | 'primary' | 'secondary'

    // Career / pensum
    career_name: string | null
    pensum_name: string | null
    pensum_total_credits: number | null

    // Academic data
    academic_year: number | null
    academic_status: string | null
    modality: string | null
    shift: string | null
    cumulative_gpa: string | null
    enrollment_date: string | null

    // Related data
    active_enrollment: ActiveEnrollment | null
    guardians: GuardianSummary[]
    enrollments: EnrollmentHistoryItem[]
}
