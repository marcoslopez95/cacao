export type GradeLevel = 'primary_secondary' | 'university'
export type GradeScaleType = 'numeric' | 'letter'
export type GradeVisibility = 'real_time' | 'manual'

export interface GradeLetterValue {
    id: number
    letter: string
    numeric_equiv: string
    is_passing: boolean
    sort_order: number
}

export interface GradeSlot {
    id: number
    name: string
    weight: string
    sort_order: number
    is_remedial: boolean
}

export interface GradeConfig {
    id: number
    level: GradeLevel
    level_label: string
    period_id: number | null
    scale_type: GradeScaleType
    scale_min: string | null
    scale_max: string | null
    passing_value: string
    slots: GradeSlot[]
    letter_values: GradeLetterValue[]
}

export interface GradeSlotFormData {
    name: string
    weight: number
    sort_order: number
    is_remedial: boolean
}

export interface GradeLetterValueFormData {
    letter: string
    numeric_equiv: number
    is_passing: boolean
    sort_order: number
}

export interface GradeConfigFormData {
    level: GradeLevel
    period_id: number | null
    scale_type: GradeScaleType
    scale_min: number | null
    scale_max: number | null
    passing_value: number
    slots: GradeSlotFormData[]
    letter_values: GradeLetterValueFormData[]
}
