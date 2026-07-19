<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3'
import { show as showStudent } from '@/routes/academic/students'
import { index as guardiansIndex } from '@/routes/academic/guardians'
import type { GuardianShowData } from '@/types/guardian'

const props = defineProps<{
    guardian: GuardianShowData
}>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Representantes', href: '/academic/guardians' },
            { title: 'Perfil de representante' },
        ],
    },
})
</script>

<template>
    <Head :title="`Representante — ${guardian.name}`" />

    <div class="space-y-6">
        <!-- ── Header ──────────────────────────────────────────── -->
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-[var(--text-primary)]">{{ guardian.name }}</h1>
                <p class="mt-1 text-sm text-[var(--text-muted)]">{{ guardian.email }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <Link :href="guardiansIndex.url()" class="btn btn-secondary btn-sm">
                    Volver
                </Link>
            </div>
        </div>

        <!-- ── Estudiantes a cargo ─────────────────────────────── -->
        <section class="rounded-xl border border-[var(--border)] bg-[var(--bg-surface)] p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-[var(--text-muted)]">Estudiantes a cargo</h2>
            <template v-if="guardian.students.length > 0">
                <ul class="divide-y divide-[var(--border)]">
                    <li
                        v-for="student in guardian.students"
                        :key="student.id"
                        class="flex items-center justify-between py-3 text-sm"
                    >
                        <div>
                            <Link :href="showStudent({ student: student.id }).url" class="font-medium text-[var(--text-primary)] hover:text-[var(--accent)]">
                                {{ student.name }}
                            </Link>
                            <span v-if="student.kinship" class="ml-2 text-xs text-[var(--text-muted)]">{{ student.kinship }}</span>
                            <span v-if="student.primary" class="ml-2 text-xs text-[var(--accent)]">Principal</span>
                            <span v-if="student.emergency_contact" class="ml-2 text-xs text-[var(--accent)]">Contacto de emergencia</span>
                        </div>
                        <span class="text-[var(--text-muted)]">{{ student.email }}</span>
                    </li>
                </ul>
            </template>
            <p v-else class="text-sm text-[var(--text-muted)]">Sin estudiantes a cargo.</p>
        </section>
    </div>
</template>
