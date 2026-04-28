export type Period = {
    id: number
    name: string
    type: 'semester' | 'year' | 'trimester'
    typeLabel: string
    startDate: string
    endDate: string
    status: 'upcoming' | 'active' | 'closed'
    statusLabel: string
    lapses: never[]
}

export type PeriodCollection = Period[]
