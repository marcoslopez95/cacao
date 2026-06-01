export type ClassSessionType = 'regular' | 'makeup' | 'advance'
export type ClassSessionStatus = 'scheduled' | 'held' | 'cancelled' | 'recovered' | 'advanced'
export type AttendanceStatus = 'present' | 'absent'

export type LinkedSession = {
    id: number
    date: string       // 'Y-m-d'
    topic: string | null
    status: ClassSessionStatus
}

export type ClassSession = {
    id: number
    sectionId: number
    type: ClassSessionType
    typeLabel: string
    status: ClassSessionStatus
    statusLabel: string
    professorPresent: boolean
    uploadedBy: string | null    // nombre del user que subió si profesor faltó
    topic: string | null
    heldAt: string | null        // 'Y-m-d'
    linkedSession: LinkedSession | null
    present: number
    absent: number
    hasRecord: boolean
}

export type ClassSessionCollection = ClassSession[]

export type AttendanceRosterEntry = {
    enrollmentDetailId: number
    studentId: number
    name: string
    initials: string
    code: string
    status: AttendanceStatus | null    // null si no hay record todavía
}

export type AttendanceSheet = {
    session: ClassSession
    roster: AttendanceRosterEntry[]
    absenceTotals: Record<number, number>    // enrollmentDetailId → count de ausencias
    sessionsCounted: number
}

export type AttendanceSectionContext = {
    id: number
    code: string
    subject: string
    cohort: string
    career: string
    careerColor: string
    teacherName: string
    teacherInitials: string
    scheduleDisplay: string
    room: string
    rosterCount: number
}

// Marks map para el formulario de pasar lista
export type AttendanceMarks = Record<number, AttendanceStatus>    // enrollmentDetailId → status
