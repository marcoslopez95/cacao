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
