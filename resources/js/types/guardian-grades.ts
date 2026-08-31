import type { StudentGradeCard } from '@/types/grade-entry'

export interface GuardianGradesStudent {
    id: number
    name: string
}

export interface GuardianGradesProps {
    grades: StudentGradeCard | null
    period: string | null
    student_name: string
    student_id: number
    students: GuardianGradesStudent[]
}
