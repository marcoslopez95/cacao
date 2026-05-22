export interface GuardianStudent {
  id: number
  name: string
  educational_level: string
  academic_year: number
  pensum_name: string | null
  uc_pensum: number
  uc_aprobadas: number
  enrollment_status: string | null
  uc_inscritas: number
  nota_promedio: null
  inasistencias: null
  subjects: Array<{ id: number; name: string }>
}

export interface GuardianDashboardProps {
  period: { name: string } | null
  students: GuardianStudent[]
}
