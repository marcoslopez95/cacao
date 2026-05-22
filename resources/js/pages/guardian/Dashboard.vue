<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatCard from '@/components/UI/AppStatCard.vue'
import Badge from '@/components/UI/AppBadge.vue'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/guardian' }],
    },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? '')

const students = [
    { name: 'Carlos García',   year: '2do año', career: 'Ingeniería de Sistemas', average: 15.8, attendance: '92%' },
    { name: 'Sofía García',    year: '4to año', career: 'Arquitectura',           average: 17.2, attendance: '98%' },
]

const recentActivity = [
    { student: 'Carlos García', type: 'grade',      detail: 'Nota en Cálculo I — 14/20',       date: '15/05/2025', variant: 'neutral' as const },
    { student: 'Sofía García',  type: 'grade',      detail: 'Nota en Diseño III — 18/20',      date: '14/05/2025', variant: 'success' as const },
    { student: 'Carlos García', type: 'attendance', detail: 'Inasistencia en Física II',       date: '12/05/2025', variant: 'warning' as const },
    { student: 'Sofía García',  type: 'enrollment', detail: 'Inscripción aprobada para 2025-2', date: '01/05/2025', variant: 'success' as const },
]
</script>

<template>
    <AppLayout>
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Header -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-terracota);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                        Portal del Representante
                    </div>
                    <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0;">
                        Bienvenido/a{{ firstName ? ', ' + firstName : '' }}
                    </h1>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:4px 0 0;">
                        Tu portal de representante está siendo preparado. Desde aquí podrás consultar el progreso académico de tus representados.
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                <StatCard label="Representados"        value="2"    footer="Estudiantes activos" :accent="true" />
                <StatCard label="Promedio combinado"   value="16,5" footer="Ambos representados" />
                <StatCard label="Asistencia promedio"  value="95%"  delta="Este período" delta-direction="up" />
                <StatCard label="Materias inscritas"   value="7"    footer="Total entre ambos" />
            </div>

            <!-- Representados -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
                <div
                    v-for="s in students"
                    :key="s.name"
                    class="card"
                >
                    <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-weight:600;color:var(--text-primary);">{{ s.name }}</div>
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-top:2px;">
                                    {{ s.year }} · {{ s.career }}
                                </div>
                            </div>
                            <Badge variant="success" dot>Activo/a</Badge>
                        </div>
                        <div style="display:flex;gap:24px;">
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:2px;">Promedio</div>
                                <div style="font-size:var(--text-lg);font-weight:700;color:var(--color-terracota);">
                                    {{ s.average }}<span style="font-size:var(--text-xs);color:var(--text-muted);font-weight:400;">/20</span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:2px;">Asistencia</div>
                                <div style="font-size:var(--text-lg);font-weight:700;color:var(--text-primary);">
                                    {{ s.attendance }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="table-wrap">
                <div class="table-toolbar">
                    <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                        Actividad reciente
                    </span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Representado/a</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in recentActivity" :key="item.student + item.detail">
                            <td style="font-weight:500;">{{ item.student }}</td>
                            <td>
                                <Badge :variant="item.variant" dot>
                                    {{ item.type === 'grade' ? 'Nota' : item.type === 'attendance' ? 'Asistencia' : 'Inscripción' }}
                                </Badge>
                            </td>
                            <td style="color:var(--text-secondary);">{{ item.detail }}</td>
                            <td style="color:var(--text-muted);">{{ item.date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
