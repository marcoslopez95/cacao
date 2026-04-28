<script setup lang="ts">
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateLapseModal from '@/components/scheduling/CreateLapseModal.vue'
import DeleteLapseModal from '@/components/scheduling/DeleteLapseModal.vue'
import EditLapseModal from '@/components/scheduling/EditLapseModal.vue'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{
    period: Period
    canCreate: boolean
    canUpdate: boolean
    canDelete: boolean
}>()

const expanded = ref(false)
const showCreate = ref(false)
const editingLapse = ref<Lapse | null>(null)
const deletingLapse = ref<Lapse | null>(null)
</script>

<template>
    <div style="margin-top:8px;">
        <button
            type="button"
            style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);color:var(--text-muted);background:none;border:none;cursor:pointer;padding:0;"
            @click="expanded = !expanded"
        >
            <span style="transition:transform 0.15s;" :style="{ transform: expanded ? 'rotate(90deg)' : 'rotate(0deg)' }">▶</span>
            Lapsos ({{ period.lapses.length }})
        </button>

        <div v-if="expanded" style="margin-top:8px;padding:12px;background:var(--papel-dark);border-radius:6px;display:flex;flex-direction:column;gap:8px;">
            <div
                v-for="lapse in period.lapses"
                :key="lapse.id"
                style="display:flex;align-items:center;justify-content:space-between;gap:8px;"
            >
                <div>
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        {{ lapse.name }}
                    </span>
                    <span style="font-size:var(--text-xs);color:var(--text-muted);margin-left:8px;">
                        {{ lapse.startDate }} — {{ lapse.endDate }}
                    </span>
                </div>
                <div style="display:flex;gap:4px;">
                    <Button
                        v-if="canUpdate"
                        variant="ghost"
                        size="sm"
                        icon-only
                        icon="edit"
                        :aria-label="`Editar ${lapse.name}`"
                        @click="editingLapse = lapse"
                    />
                    <Button
                        v-if="canDelete"
                        variant="ghost"
                        size="sm"
                        icon-only
                        icon="trash"
                        :aria-label="`Eliminar ${lapse.name}`"
                        @click="deletingLapse = lapse"
                    />
                </div>
            </div>

            <div v-if="period.lapses.length === 0" style="font-size:var(--text-xs);color:var(--text-muted);">
                Sin lapsos registrados.
            </div>

            <Button
                v-if="canCreate"
                variant="ghost"
                size="sm"
                icon="plus"
                style="align-self:flex-start;margin-top:4px;"
                @click="showCreate = true"
            >
                Agregar lapso
            </Button>
        </div>
    </div>

    <CreateLapseModal v-model:open="showCreate" :period="period" />

    <EditLapseModal
        v-if="editingLapse"
        :open="!!editingLapse"
        :period="period"
        :lapse="editingLapse"
        @update:open="v => { if (!v) editingLapse = null }"
    />

    <DeleteLapseModal
        v-if="deletingLapse"
        :open="!!deletingLapse"
        :period="period"
        :lapse="deletingLapse"
        @update:open="v => { if (!v) deletingLapse = null }"
    />
</template>
