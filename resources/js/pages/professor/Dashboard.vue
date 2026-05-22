<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatCard from '@/components/UI/AppStatCard.vue'
import Badge from '@/components/UI/AppBadge.vue'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/professor' }],
    },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? '')

const sections = [
    { code: 'SEC-01', subject: 'Cálculo I',        students: 28, day: 'Lun/Mié', time: '07:00–08:30', room: 'A-101', status: 'active' as const },
    { code: 'SEC-03', subject: 'Física II',         students: 24, day: 'Mar/Jue', time: '09:00–10:30', room: 'B-202', status: 'active' as const },
    { code: 'SEC-07', subject: 'Programación I',    students: 30, day: 'Vie',     time: '11:00–12:30', room: 'Lab-1', status: 'active' as const },
    { code: 'SEC-02', subject: 'Álgebra Lineal',    students: 22, day: 'Lun/Jue', time: '14:00–15:30', room: 'A-104', status: 'active' as const },
]

const pendingItems = [
    { type: 'grade',      subject: 'Cálculo I',     detail: '28 notas por cargar',      urgency: 'high'   as const },
    { type: 'attendance', subject: 'Física II',     detail: 'Asistencia del martes',     urgency: 'medium' as const },
    { type: 'grade',      subject: 'Programación I', detail: '12 entregas por revisar', urgency: 'medium' as const },
]

const urgencyVariant = { high: 'danger', medium: 'warning', low: 'neutral' } as const
const urgencyLabel   = { high: 'Urgente', medium: 'Pendiente', low: 'Sin urgencia' } as const
</script>

<template>
    <AppLayout>
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Header -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-terracota);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                        Portal del Profesor
                    </div>
                    <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0;">
                        Bienvenido{{ firstName ? ', ' + firstName : '' }}
                    </h1>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:4px 0 0;">
                        Tu portal académico está siendo preparado. Pronto podrás gestionar tus secciones, horarios y notas desde aquí.
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                <StatCard label="Secciones activas"  value="4"  footer="Período 2025-2" :accent="true" />
                <StatCard label="Estudiantes totales" value="104" footer="En todas las secciones" />
                <StatCard label="Notas pendientes"   value="40"  delta="Esta semana" delta-direction="up" />
                <StatCard label="Asistencias hoy"    value="3"   footer="Clases programadas" />
            </div>

            <!-- Mis secciones -->
            <div class="table-wrap">
                <div class="table-toolbar">
                    <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                        Mis secciones
                    </span>
                    <Badge variant="accent">Período 2025-2</Badge>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sección</th>
                            <th>Materia</th>
                            <th>Días / Hora</th>
                            <th>Aula</th>
                            <th>Estudiantes</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sections" :key="s.code">
                            <td style="font-weight:600;color:var(--color-terracota);">{{ s.code }}</td>
                            <td style="font-weight:500;">{{ s.subject }}</td>
                            <td style="color:var(--text-secondary);">{{ s.day }} · {{ s.time }}</td>
                            <td style="color:var(--text-muted);">{{ s.room }}</td>
                            <td style="color:var(--text-secondary);">{{ s.students }}</td>
                            <td>
                                <Badge variant="success" dot>Activa</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tareas pendientes -->
            <div class="table-wrap">
                <div class="table-toolbar">
                    <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                        Tareas pendientes
                    </span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Materia</th>
                            <th>Detalle</th>
                            <th>Prioridad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in pendingItems" :key="item.subject + item.type">
                            <td style="color:var(--text-muted);text-transform:capitalize;">
                                {{ item.type === 'grade' ? 'Notas' : 'Asistencia' }}
                            </td>
                            <td style="font-weight:500;">{{ item.subject }}</td>
                            <td style="color:var(--text-secondary);">{{ item.detail }}</td>
                            <td>
                                <Badge :variant="urgencyVariant[item.urgency]" dot>
                                    {{ urgencyLabel[item.urgency] }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
