<script setup lang="ts">
import { ref, watch } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import type { CoordinationAssignment, CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const assignments = ref<CoordinationAssignment[]>([])
const loading = ref(false)

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) {
            assignments.value = []
            return
        }

        loading.value = true
        try {
            const res = await fetch(`/security/coordinations/${props.coordination.id}/assignments`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
            assignments.value = await res.json()
        } finally {
            loading.value = false
        }
    },
)

function close(v: boolean): void {
    emit('update:open', v)
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <Modal :open="open" :title="`Historial — ${coordination.name}`" size="lg" @update:open="close">
        <div v-if="loading" style="text-align:center;padding:32px;color:var(--text-muted);font-size:var(--text-sm);">
            Cargando historial...
        </div>

        <div v-else-if="assignments.length === 0" style="text-align:center;padding:32px;color:var(--text-muted);font-size:var(--text-sm);">
            No hay asignaciones registradas para esta coordinación.
        </div>

        <div v-else class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Coordinador</th>
                        <th>Asignado por</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in assignments" :key="a.id">
                        <td style="font-weight:500;">{{ a.user.name }}</td>
                        <td style="color:var(--text-secondary);">{{ a.assigned_by.name }}</td>
                        <td style="color:var(--text-secondary);">{{ formatDate(a.assigned_at) }}</td>
                        <td>
                            <Badge v-if="!a.ended_at" variant="success">Activo</Badge>
                            <span v-else style="color:var(--text-secondary);">{{ formatDate(a.ended_at) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:24px;">
            <Button variant="ghost" @click="close(false)">Cerrar</Button>
        </div>
    </Modal>
</template>
