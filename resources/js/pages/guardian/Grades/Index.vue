<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { StudentGradeCard, StudentGradeSubject, StudentGradeSlot } from '@/types/grade-entry'

type Props = {
    grades: StudentGradeCard | null
    period: string | null
    student_name: string
}

defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Representante', href: '#' },
            { title: 'Notas del representado' },
        ],
    },
})

function passedColor(passed: boolean | null): string {
    if (passed === null) {
return 'color:var(--text-muted)'
}

    return passed ? 'color:#27ae60' : 'color:#c0392b'
}

function passedLabel(passed: boolean | null): string {
    if (passed === null) {
return 'Sin nota definitiva'
}

    return passed ? 'Aprobado' : 'Reprobado'
}

function visibleSlots(subject: StudentGradeSubject): StudentGradeSlot[] {
    return subject.slots.filter(s => !s.is_remedial && s.is_published)
}
</script>

<template>
    <Head title="Notas del representado" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div>
            <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                Notas de {{ student_name }}
            </h1>
            <p v-if="period" style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                {{ period }}
            </p>
        </div>

        <div v-if="!grades" style="text-align:center;padding:48px 24px;color:var(--text-muted);">
            <p style="font-size:var(--text-sm);">El representado no tiene inscripción activa en el período actual.</p>
        </div>

        <div v-else-if="grades.subjects.length === 0" style="text-align:center;padding:48px 24px;color:var(--text-muted);">
            <p style="font-size:var(--text-sm);">No hay materias inscritas para mostrar.</p>
        </div>

        <div v-else style="display:flex;flex-direction:column;gap:16px;">
            <div
                v-for="subject in grades.subjects"
                :key="subject.enrollment_detail_id"
                style="background:white;border:1px solid var(--color-borde);border-radius:8px;padding:20px 24px;"
            >
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;">
                    <h3 style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);margin:0;">
                        {{ subject.subject_name }}
                    </h3>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:var(--text-xl);font-weight:700;" :style="passedColor(subject.passed)">
                            {{ subject.final_grade ?? '—' }}
                        </div>
                        <div style="font-size:var(--text-xs);" :style="passedColor(subject.passed)">
                            {{ passedLabel(subject.passed) }}
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <div
                        v-for="slot in visibleSlots(subject)"
                        :key="slot.slot_id"
                        style="background:var(--color-papel);border-radius:6px;padding:10px 14px;min-width:120px;"
                    >
                        <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:4px;">
                            {{ slot.slot_name }} ({{ slot.weight }}%)
                        </div>
                        <div style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);">
                            {{ slot.value ?? '—' }}
                        </div>
                    </div>

                    <div
                        v-if="visibleSlots(subject).length === 0"
                        style="font-size:var(--text-sm);color:var(--text-muted);padding:10px 0;"
                    >
                        Las notas aún no han sido publicadas.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
