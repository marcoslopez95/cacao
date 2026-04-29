export type Lapse = {
    id: number
    number: number
    name: string
    startDate: string
    endDate: string
}

export type Period = {
    id: number
    name: string
    type: 'semester' | 'year' | 'trimester'
    typeLabel: string
    startDate: string
    endDate: string
    status: 'upcoming' | 'active' | 'closed'
    statusLabel: string
    lapses: Lapse[]
}

export type PeriodCollection = Period[]

export type AvailableUser = {
    id: number
    name: string
    email: string
}

export type Professor = {
    id: number
    weeklyHourLimit: number
    active: boolean
    user: {
        id: number
        name: string
        email: string
    }
}

export type ProfessorCollection = Professor[]

export type SectionPeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester'
}

export type SectionSubject = {
    id: number
    name: string
    code: string
}

export type SectionClassroom = {
    id: number
    identifier: string
    capacity: number
}

export type UniversitySection = {
    id: number
    type: 'university'
    code: string
    capacity: number
    period: SectionPeriod
    subject: SectionSubject
    theoryClassroom: SectionClassroom | null
    labClassroom: SectionClassroom | null
}

export type UniversitySectionCollection = UniversitySection[]

export type AvailablePeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester'
}

export type SubjectForSection = {
    id: number
    name: string
    code: string
    pensumPeriodType: string | null
}

export type ClassroomForSection = {
    id: number
    identifier: string
    type: 'theory' | 'laboratory'
    capacity: number
}
