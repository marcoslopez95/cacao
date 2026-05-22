export interface TodaySchedule {
  section_id: number
  subject_name: string
  section_code: string
  classroom_name: string
  students_count: number
  start_time: string
  end_time: string
  is_current: boolean
}

export interface ProfessorDashboardProps {
  period: { name: string; type: string } | null
  sections_count: number
  total_students: number
  hours_per_week: number
  today_label: string
  today_schedules: TodaySchedule[]
}
