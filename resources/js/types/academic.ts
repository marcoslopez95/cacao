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
