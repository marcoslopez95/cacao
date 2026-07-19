<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3'
import { show as studentsShow } from '@/routes/academic/students'
import type { StudentGradeCard, StudentGradeSubject, StudentGradeSlot } from '@/types/grade-entry'

interface EnrollmentStudent {
    id: number
    name: string
}

const props = defineProps<{
    student: EnrollmentStudent
    grades: StudentGradeCard
}>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Académico', href: '#' },
        { title: 'Estudiantes', href: '/academic/students' },
        { title: props.student.name, href: studentsShow.url({ student: props.student.id }) },
        { title: props.grades.period },
    ],
})

function passedLabel(passed: boolean | null): string {
    if (passed === null) {
        return 'Sin nota definitiva'
    }

    return passed ? 'Aprobado' : 'Reprobado'
}

function passedClass(passed: boolean | null): string {
    if (passed === null) {
        return 'text-[var(--text-muted)]'
    }

    return passed ? 'text-emerald-600' : 'text-red-600'
}

function visibleSlots(subject: StudentGradeSubject): StudentGradeSlot[] {
    return subject.slots.filter(slot => !slot.is_remedial)
}
</script>

<template>
    <Head :title="`Notas — ${student.name}`" />

    <div class="space-y-6">
        <!-- ── Header ──────────────────────────────────────────── -->
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-[var(--text-primary)] tracking-tight">{{ student.name }}</h1>
                <p class="mt-1 text-sm text-[var(--text-muted)]">{{ grades.period }}</p>
            </div>
            <Link
                :href="studentsShow.url({ student: student.id })"
                class="btn btn-secondary btn-sm shrink-0"
            >
                Volver
            </Link>
        </div>

        <!-- ── Notas por materia ──────────────────────────────── -->
        <template v-if="grades.subjects.length > 0">
            <section
                v-for="subject in grades.subjects"
                :key="subject.enrollment_detail_id"
                class="rounded-xl border border-[var(--border)] bg-[var(--bg-surface)] p-5"
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-[var(--text-muted)]">
                        {{ subject.subject_name }}
                    </h2>
                    <div class="text-right shrink-0">
                        <div class="text-xl font-bold" :class="passedClass(subject.passed)">
                            {{ subject.final_grade ?? '—' }}
                        </div>
                        <div class="text-xs" :class="passedClass(subject.passed)">
                            {{ passedLabel(subject.passed) }}
                        </div>
                    </div>
                </div>

                <div v-if="visibleSlots(subject).length > 0" class="flex flex-wrap gap-3">
                    <div
                        v-for="slot in visibleSlots(subject)"
                        :key="slot.slot_id"
                        class="min-w-[140px] rounded-lg bg-[var(--bg-surface-2)] p-3"
                    >
                        <div class="mb-1 text-xs text-[var(--text-muted)]">
                            {{ slot.slot_name }} ({{ slot.weight }}%)
                        </div>
                        <div class="text-base font-semibold text-[var(--text-primary)]">
                            {{ slot.value ?? '—' }}
                        </div>
                        <div v-if="slot.children.length > 0" class="mt-1.5 space-y-0.5 border-t border-[var(--border)] pt-1.5">
                            <div
                                v-for="(child, i) in slot.children"
                                :key="i"
                                class="flex justify-between text-xs text-[var(--text-muted)]"
                            >
                                <span>{{ child.name }} ({{ child.weight }}%)</span>
                                <span>{{ child.value ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-[var(--text-muted)]">Las notas aún no han sido publicadas.</p>
            </section>
        </template>
        <p v-else class="text-sm text-[var(--text-muted)]">No hay materias inscritas para mostrar.</p>
    </div>
</template>
