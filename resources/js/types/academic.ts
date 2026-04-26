export type CareerCategory = {
  id: number
  name: string
}

export type Career = {
  id: number
  name: string
  code: string
  active: boolean
  category: CareerCategory
  pensumsCount: number
}

export type Pensum = {
  id: number
  name: string
  periodType: 'semester' | 'year'
  totalPeriods: number
  isActive: boolean
  subjectsCount: number
}
