<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatCard from '@/components/UI/AppStatCard.vue'
import Badge from '@/components/UI/AppBadge.vue'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/student' }],
    },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? '')

const enrolledSubjects = [
    { code: 'MAT-201', subject: 'Cálculo I',        section: 'SEC-01', professor: 'Prof. García',   credits: 4, status: 'approved' as const },
    { code: 'FIS-102', subject: 'Física II',         section: 'SEC-03', professor: 'Prof. Martínez', credits: 3, status: 'approved' as const },
    { code: 'INF-110', subject: 'Programación I',    section: 'SEC-07', professor: 'Prof. López',    credits: 4, status: 'pending'  as const },
]

const recentGrades = [
    { subject: 'Cálculo I',     activity: 'Parcial I',  grade: 18, max: 20, date: '15/05/2025' },
    { subject: 'Física II',     activity: 'Quiz 2',     grade: 14, max: 20, date: '12/05/2025' },
    { subject: 'Física II',     activity: 'Lab 1',      grade: 17, max: 20, date: '08/05/2025' },
]

const statusVariant = { approved: 'success', pending: 'warning', rejected: 'danger' } as const
const statusLabel   = { approved: 'Inscrita', pending: 'Pendiente', rejected: 'Rechazada' } as const

function gradeColor(grade: number, max: number): string {
    const pct = grade / max
    if (pct >= 0.9) return 'var(--color-success, #16a34a)'
    if (pct >= 0.7) return 'var(--text-secondary)'
    return 'var(--color-danger, #dc2626)'
}
</script>

<template>
    <AppLayout>
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Header -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-terracota);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                        Portal del Estudiante
                    </div>
                    <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0;">
                        Bienvenido/a{{ firstName ? ', ' + firstName : '' }}
                    </h1>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:4px 0 0;">
                        Tu portal estudiantil está siendo preparado. Desde aquí podrás gestionar tu inscripción, consultar tus notas y mucho más.
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                <StatCard label="Materias inscritas" value="3"   footer="Período 2025-2" :accent="true" />
                <StatCard label="Unidades crédito"   value="11"  footer="Este período" />
                <StatCard label="Promedio actual"    value="16,3" delta="Sobre 20 pts" delta-direction="up" />
                <StatCard label="Asistencia"         value="94%" footer="Todas las materias" />
            </div>

            <!-- Materias inscritas -->
            <div class="table-wrap">
                <div class="table-toolbar">
                    <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                        Materias inscritas
                    </span>
                    <Badge variant="accent">Período 2025-2</Badge>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Sección</th>
                            <th>Profesor/a</th>
                            <th>UC</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in enrolledSubjects" :key="s.code">
                            <td style="font-weight:600;color:var(--color-terracota);">{{ s.code }}</td>
                            <td style="font-weight:500;">{{ s.subject }}</td>
                            <td style="color:var(--text-muted);">{{ s.section }}</td>
                            <td style="color:var(--text-secondary);">{{ s.professor }}</td>
                            <td style="color:var(--text-muted);">{{ s.credits }}</td>
                            <td>
                                <Badge :variant="statusVariant[s.status]" dot>
                                    {{ statusLabel[s.status] }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Notas recientes -->
            <div class="table-wrap">
                <div class="table-toolbar">
                    <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                        Notas recientes
                    </span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Actividad</th>
                            <th>Nota</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="g in recentGrades" :key="g.subject + g.activity">
                            <td style="font-weight:500;">{{ g.subject }}</td>
                            <td style="color:var(--text-secondary);">{{ g.activity }}</td>
                            <td>
                                <span :style="{ fontWeight: 600, color: gradeColor(g.grade, g.max) }">
                                    {{ g.grade }}/{{ g.max }}
                                </span>
                            </td>
                            <td style="color:var(--text-muted);">{{ g.date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
