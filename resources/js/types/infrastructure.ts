export type Building = {
    id: number
    name: string
    classroomsCount: number
}

export type BuildingCollection = Building[]

export type Classroom = {
    id: number
    identifier: string
    type: 'theory' | 'laboratory'
    capacity: number
    building: {
        id: number
        name: string
    }
}

export type ClassroomCollection = Classroom[]
