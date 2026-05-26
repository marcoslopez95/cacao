<script setup lang="ts">
import { computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import { index as studentsIndex } from '@/routes/academic/students'
import { edit as editUser } from '@/actions/App/Http/Controllers/Security/UserController'
import type { StudentShowData } from '@/types/studentShow'

const props = defineProps<{
    student: StudentShowData
}>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Estudiantes', href: '/academic/students' },
            { title: 'Perfil de estudiante' },
        ],
    },
})

const isSchoolLevel = computed<boolean>(
    () => props.student.educational_level === 'primary' || props.student.educational_level === 'secondary'
)

const educationalLevelLabel = computed<string>(() => {
    const map: Record<string, string> = {
        university: 'Universitario',
        primary: 'Primaria',
        secondary: 'Bachillerato',
    }
    return map[props.student.educational_level] ?? props.student.educational_level
})

const enrollmentStatusLabel = (status: string | null): string => {
    const map: Record<string, string> = {
        draft: 'Borrador',
        confirmed: 'Confirmada',
        approved: 'Aprobada',
        rejected: 'Rechazada',
    }
    return status ? (map[status] ?? status) : '—'
}
</script>

<template>
    <Head :title="`Estudiante — ${student.name}`" />

    <div class="space-y-6">
        <!-- ── Header ──────────────────────────────────────────── -->
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-tinta tracking-tight">{{ student.name }}</h1>
                <p class="mt-1 text-sm text-gris">
                    {{ educationalLevelLabel }}
                    <span v-if="student.academic_status" class="ml-2 text-gris-light">· {{ student.academic_status }}</span>
                </p>
            </div>
            <div class="flex gap-2 shrink-0">
                <Link
                    :href="studentsIndex.url()"
                    class="btn btn-secondary btn-sm"
                >
                    Volver
                </Link>
                <Link
                    :href="editUser({ user: student.user_id }).url"
                    class="btn btn-primary btn-sm"
                >
                    Editar
                </Link>
            </div>
        </div>

        <!-- ── Identidad ──────────────────────────────────────── -->
        <section class="rounded-xl border border-gris-borde bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gris">Identidad</h2>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-gris">Correo electrónico</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.email }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Cédula</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.cedula ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Código de estudiante</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.student_code ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Fecha de ingreso</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.enrollment_date ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <!-- ── Carrera y Pensum ───────────────────────────────── -->
        <section class="rounded-xl border border-gris-borde bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gris">Carrera y Pensum</h2>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-gris">Carrera</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.career_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Pensum</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.pensum_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Año académico</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.academic_year ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Modalidad</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.modality ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Turno</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.shift ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gris">Promedio acumulado</dt>
                    <dd class="mt-0.5 font-medium text-tinta">{{ student.cumulative_gpa ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <!-- ── Inscripción activa ─────────────────────────────── -->
        <section class="rounded-xl border border-gris-borde bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gris">Inscripción activa</h2>
            <template v-if="student.active_enrollment">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-gris">Período</dt>
                        <dd class="mt-0.5 font-medium text-tinta">{{ student.active_enrollment.period_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gris">Estado</dt>
                        <dd class="mt-0.5 font-medium text-tinta">{{ enrollmentStatusLabel(student.active_enrollment.status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gris">UC inscritas</dt>
                        <dd class="mt-0.5 font-medium text-tinta">{{ student.active_enrollment.uc_inscritas }}</dd>
                    </div>
                    <div>
                        <dt class="text-gris">UC disponibles</dt>
                        <dd class="mt-0.5 font-medium text-tinta">{{ student.active_enrollment.uc_disponibles }}</dd>
                    </div>
                </dl>
            </template>
            <p v-else class="text-sm text-gris">Sin inscripción en el período activo.</p>
        </section>

        <!-- ── Representantes (solo primaria y bachillerato) ──── -->
        <section v-if="isSchoolLevel" class="rounded-xl border border-gris-borde bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gris">Representantes</h2>
            <template v-if="student.guardians.length > 0">
                <ul class="divide-y divide-gris-borde">
                    <li
                        v-for="guardian in student.guardians"
                        :key="guardian.id"
                        class="flex items-center justify-between py-3 text-sm"
                    >
                        <div>
                            <span class="font-medium text-tinta">{{ guardian.name }}</span>
                            <span v-if="guardian.primary" class="ml-2 text-xs text-terracota">Principal</span>
                        </div>
                        <span class="text-gris">{{ guardian.email }}</span>
                    </li>
                </ul>
            </template>
            <p v-else class="text-sm text-gris">Sin representantes registrados.</p>
        </section>

        <!-- ── Historial de inscripciones ─────────────────────── -->
        <section class="rounded-xl border border-gris-borde bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gris">Historial de inscripciones</h2>
            <template v-if="student.enrollments.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gris-borde text-left text-xs uppercase tracking-wider text-gris">
                            <th class="pb-2 pr-4">Período</th>
                            <th class="pb-2 pr-4">Estado</th>
                            <th class="pb-2 pr-4">UC inscritas</th>
                            <th class="pb-2">UC disponibles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gris-borde">
                        <tr
                            v-for="enrollment in student.enrollments"
                            :key="enrollment.id"
                            class="text-tinta"
                        >
                            <td class="py-2.5 pr-4">{{ enrollment.period_name ?? '—' }}</td>
                            <td class="py-2.5 pr-4">{{ enrollmentStatusLabel(enrollment.status) }}</td>
                            <td class="py-2.5 pr-4">{{ enrollment.uc_inscritas }}</td>
                            <td class="py-2.5">{{ enrollment.uc_disponibles }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>
            <p v-else class="text-sm text-gris">Sin historial de inscripciones.</p>
        </section>
    </div>
</template>
