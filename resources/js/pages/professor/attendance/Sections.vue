<script setup lang="ts">
import { setLayoutProps } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { index as attendanceIndex } from '@/actions/App/Http/Controllers/Professor/AttendanceController'
import AppIcon from '@/components/UI/AppIcon.vue'

type SectionSummary = {
    id: number
    code: string
    subject: string
    roster_count: number
    sessions_held: number
    sessions_pending: number
}

type Props = {
    period: string | null
    sections: SectionSummary[]
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Profesor', href: '#' },
        { title: 'Asistencia' },
    ],
})

function goToSection(section: SectionSummary): void {
    router.visit(attendanceIndex({ section: section.id }).url)
}
</script>

<template>
    <div>
        <div class="page-header">
            <div>
                <h1>Asistencia</h1>
                <p v-if="props.period" class="text-muted">
                    Período {{ props.period }} · Seleccioná la sección para gestionar la asistencia
                </p>
                <p v-else class="text-muted">
                    No hay un período académico activo en este momento.
                </p>
            </div>
        </div>

        <div v-if="props.sections.length === 0" class="att-empty" style="margin-top: 48px;">
            <div class="ic"><AppIcon name="calendar" :size="20" /></div>
            <h3>Sin secciones asignadas</h3>
            <p>No tenés secciones activas como profesor titular en el período actual.</p>
        </div>

        <div v-else style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; margin-top: 8px;">
            <div
                v-for="section in props.sections"
                :key="section.id"
                class="card"
                style="cursor: pointer; transition: box-shadow 0.15s, transform 0.08s;"
                @click="goToSection(section)"
                @mouseenter="($event.currentTarget as HTMLElement).style.cssText += ';box-shadow:var(--shadow-md);transform:translateY(-1px)'"
                @mouseleave="($event.currentTarget as HTMLElement).style.cssText += ';box-shadow:var(--shadow-xs);transform:none'"
            >
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                    <div style="min-width: 0;">
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px;">
                            {{ section.subject }}
                        </div>
                        <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">
                            {{ section.code }}
                        </div>
                    </div>
                    <AppIcon name="chevronRight" :size="16" style="color: var(--text-muted); flex-shrink: 0; margin-top: 2px;" />
                </div>

                <div style="display: flex; gap: 20px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary);">
                        <AppIcon name="users" :size="13" style="color: var(--text-muted);" />
                        {{ section.roster_count }} estudiantes
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary);">
                        <AppIcon name="check" :size="13" style="color: var(--success);" />
                        {{ section.sessions_held }} registradas
                    </div>
                    <div v-if="section.sessions_pending > 0" style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--warning-fg);">
                        <AppIcon name="clock" :size="13" />
                        {{ section.sessions_pending }} pendientes
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
