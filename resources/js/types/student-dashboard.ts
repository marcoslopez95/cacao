export interface StudentTodaySchedule {
  subject_name: string
  section_code: string
  classroom_name: string
  start_time: string
  end_time: string
  is_current: boolean
}

export interface StudentGuardianSummary {
  name: string
  email: string
  phone: string | null
  kinship: string | null
  is_primary: boolean
}

export interface StudentDashboardProps {
  period: { name: string } | null
  enrollment: { id: number; status: string; uc_inscritas: number } | null
  subjects_count: number
  uc_pensum: number
  uc_aprobadas: number
  today_label: string
  today_schedules: StudentTodaySchedule[]
  guardians: StudentGuardianSummary[]
}
