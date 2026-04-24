<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StatCard from '@/components/base/StatCard.vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/' }],
    },
})

// TodaySchedule
const RANGE_START = 7
const RANGE_HOURS = 12

interface Slot {
    start: number
    end: number
    title: string
    room: string
    section: string
    status: 'done' | 'now' | 'next'
}

const slots: Slot[] = [
    { start: 7,   end: 8.5,  title: 'Cálculo I',        room: 'A-101', section: 'SEC-01', status: 'done' },
    { start: 9,   end: 10.5, title: 'Física II',         room: 'B-202', section: 'SEC-03', status: 'now'  },
    { start: 11,  end: 12.5, title: 'Programación I',    room: 'Lab-1', section: 'SEC-07', status: 'next' },
    { start: 14,  end: 15.5, title: 'Álgebra Lineal',    room: 'A-104', section: 'SEC-02', status: 'next' },
]

const nowDecimal = ref(0)
let timer: ReturnType<typeof setInterval>

function updateNow(): void {
    const d = new Date()
    nowDecimal.value = d.getHours() + d.getMinutes() / 60
}

onMounted(() => { updateNow(); timer = setInterval(updateNow, 60_000) })
onUnmounted(() => clearInterval(timer))

function slotStyle(s: Slot): Record<string, string> {
    const left = ((s.start - RANGE_START) / RANGE_HOURS) * 100
    const width = ((s.end - s.start) / RANGE_HOURS) * 100
    return { left: `${left}%`, width: `${width}%` }
}

const nowPct = computed(() => {
    const pct = ((nowDecimal.value - RANGE_START) / RANGE_HOURS) * 100
    return Math.min(Math.max(pct, 0), 100)
})

// Enrollments table mock
const enrollments = [
    { id: 1, student: 'María González',  subject: 'Cálculo I',     section: 'SEC-01', status: 'approved' as const },
    { id: 2, student: 'Carlos Pérez',    subject: 'Física II',     section: 'SEC-03', status: 'pending'  as const },
    { id: 3, student: 'Ana Rodríguez',   subject: 'Programación I', section: 'SEC-07', status: 'approved' as const },
    { id: 4, student: 'Luis Martínez',   subject: 'Álgebra Lineal', section: 'SEC-02', status: 'rejected' as const },
    { id: 5, student: 'Sofía Hernández', subject: 'Cálculo I',     section: 'SEC-01', status: 'pending'  as const },
]

const statusVariant = { approved: 'success', pending: 'warning', rejected: 'danger' } as const
const statusLabel   = { approved: 'Aprobada', pending: 'Pendiente', rejected: 'Rechazada' } as const

const period = ref<'week' | 'month' | 'period'>('month')
</script>

<template>
    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0;">
                    Bienvenido{{ user?.name ? ', ' + user.name.split(' ')[0] : '' }}
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:4px 0 0;">
                    Período académico 2025-2 · Semana 14
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="display:inline-flex;background:var(--bg-sunken);border-radius:var(--radius-md);padding:3px;">
                    <button
                        v-for="opt in (['week','month','period'] as const)"
                        :key="opt"
                        :class="['btn', 'btn-sm', period === opt ? 'btn-secondary' : 'btn-ghost']"
                        @click="period = opt"
                    >
                        {{ opt === 'week' ? 'Semana' : opt === 'month' ? 'Mes' : 'Período' }}
                    </button>
                </div>
                <Button icon="download" variant="secondary" size="sm">Exportar</Button>
            </div>
        </div>

        <!-- TodaySchedule -->
        <div>
            <div style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">
                Horario de hoy
            </div>
            <div class="tsched">
                <div
                    class="tsched-now-marker"
                    :style="{ left: nowPct + '%' }"
                />
                <div
                    v-for="s in slots"
                    :key="s.title"
                    :class="['tsched-slot', s.status]"
                    :style="slotStyle(s)"
                >
                    <span>{{ s.title }}</span>
                    <span style="opacity:0.7;">{{ s.room }}</span>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <StatCard label="Inscripciones activas" value="1,284" delta="+12% vs mes anterior" delta-direction="up" :accent="true" />
            <StatCard label="Secciones abiertas"    value="87"    delta="+3 esta semana" delta-direction="up" />
            <StatCard label="Profesores activos"    value="64"    footer="En 14 departamentos" />
            <StatCard label="Tasa de aprobación"    value="91%"   delta="-2% vs período anterior" delta-direction="down" />
        </div>

        <!-- Enrollments table -->
        <div class="table-wrap">
            <div class="table-toolbar">
                <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                    Inscripciones recientes
                </span>
                <Button variant="ghost" size="sm" icon="filter">Filtrar</Button>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Materia</th>
                        <th>Sección</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in enrollments" :key="e.id">
                        <td style="font-weight:500;">{{ e.student }}</td>
                        <td style="color:var(--text-secondary);">{{ e.subject }}</td>
                        <td style="color:var(--text-muted);">{{ e.section }}</td>
                        <td>
                            <Badge :variant="statusVariant[e.status]" dot>
                                {{ statusLabel[e.status] }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
