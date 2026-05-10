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
    type: 'semester' | 'trimester' | 'year'
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

export type SchoolSectionSubject = {
    id: number
    name: string
    code: string
}

export type SchoolSectionTeacher = {
    id: number
    user: {
        id: number
        name: string
    }
}

export type SchoolSectionClassroom = {
    id: number
    identifier: string
    capacity: number
}

export type SchoolSectionPensum = {
    id: number
    name: string
    career: {
        id: number
        name: string
    }
}

export type SchoolSection = {
    id: number
    type: 'school'
    grade: number
    letter: string
    code: string
    capacity: number
    period: SectionPeriod
    pensum: SchoolSectionPensum
    mainTeacher: SchoolSectionTeacher | null
    classroom: SchoolSectionClassroom | null
    subjects: SchoolSectionSubject[]
}

export type SchoolSectionCollection = SchoolSection[]

export type PensumForSection = {
    id: number
    name: string
    totalPeriods: number
    career: {
        id: number
        name: string
    }
}

export type ProfessorForSection = {
    id: number
    user: {
        id: number
        name: string
    }
}

export type ScheduleSection = {
    id: number
    code: string
    type: 'university' | 'school'
}

export type ScheduleProfessor = {
    id: number
    user: { name: string }
}

export type ScheduleClassroom = {
    id: number
    identifier: string
}

export type ScheduleSubject = {
    id: number
    name: string
    code: string
}

export type Schedule = {
    id: number
    section: ScheduleSection
    professor: ScheduleProfessor
    classroom: ScheduleClassroom
    subject: ScheduleSubject
    career: { id: number; name: string } | null
    dayOfWeek: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday'
    dayLabel: string
    startTime: string
    endTime: string
    type: 'theory' | 'lab'
    typeLabel: string
    validFrom: string
    validUntil: string | null
}

export type ScheduleCollection = Schedule[]

export type ScheduleAvailablePeriod = {
    id: number
    name: string
}

export type ScheduleAvailableSection = {
    id: number
    code: string
    type: 'university' | 'school'
    periodId: number
    periodName: string
}

export type ScheduleAvailableProfessor = {
    id: number
    name: string
    weeklyHourLimit: number
    currentWeeklyHours: number
}

export type ScheduleAvailableClassroom = {
    id: number
    identifier: string
}

export type ScheduleAvailableSubject = {
    id: number
    name: string
    code: string
}
