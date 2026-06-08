<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3'
import { index, create, edit } from '@/routes/security/grade-configs'
import type { GradeConfig } from '@/types/grade-config'

type Props = {
    configs: GradeConfig[]
    levels: Array<{ value: string; label: string }>
    can: { create: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Configuración de notas', href: index.url() },
        ],
    },
})

function scaleLabel(config: GradeConfig): string {
    if (config.scale_type === 'letter') {
return 'Letras (A–F)'
}

    return `${config.scale_min} – ${config.scale_max}`
}
</script>

<template>
    <Head title="Configuración de notas" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Configuración de notas
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Define la estructura de evaluación por nivel educativo
                </p>
            </div>
            <Link
                v-if="props.can.create"
                :href="create.url()"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--color-terracota);color:white;border-radius:6px;font-size:var(--text-sm);font-weight:500;text-decoration:none;"
            >
                + Nueva configuración
            </Link>
        </div>

        <div v-if="configs.length === 0" style="text-align:center;padding:48px 24px;color:var(--text-muted);">
            <p style="font-size:var(--text-sm);">No hay configuraciones de notas definidas aún.</p>
            <Link v-if="props.can.create" :href="create.url()" style="color:var(--color-terracota);font-size:var(--text-sm);">
                Crear la primera configuración
            </Link>
        </div>

        <div v-else style="display:flex;flex-direction:column;gap:12px;">
            <div
                v-for="config in configs"
                :key="config.id"
                style="background:white;border:1px solid var(--color-borde);border-radius:8px;padding:20px 24px;"
            >
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);">
                                {{ config.level_label }}
                            </span>
                            <span style="font-size:var(--text-xs);padding:2px 8px;border-radius:99px;background:var(--color-terracota-light);color:var(--color-terracota-text);">
                                {{ scaleLabel(config) }} · Aprueba ≥ {{ config.passing_value }}
                            </span>
                        </div>

                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <span
                                v-for="slot in config.slots"
                                :key="slot.id"
                                style="font-size:var(--text-xs);padding:3px 10px;border-radius:99px;background:var(--color-papel);color:var(--text-secondary);border:1px solid var(--color-borde);"
                            >
                                <template v-if="slot.is_remedial">⚠ {{ slot.name }}</template>
                                <template v-else>{{ slot.name }} ({{ slot.weight }}%)</template>
                            </span>
                        </div>
                    </div>

                    <Link
                        :href="edit.url(config.id)"
                        style="flex-shrink:0;padding:6px 14px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);color:var(--text-secondary);text-decoration:none;"
                    >
                        Editar
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
