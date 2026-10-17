<script setup lang="ts">
import { computed } from 'vue'
import UserFormAutosave from '@/components/security/UserForm/UserFormAutosave.vue'
import type { AutosaveState } from '@/types/userForm'

const props = withDefaults(defineProps<{
    completion: { pct: number; sectionsComplete: Set<number> }
    totalSections: number
    autosave: AutosaveState
    /** Extra gate on top of the 50% completion rule (e.g. required fields). */
    canSubmitExtra?: boolean
    processing?: boolean
    showSaveDraft?: boolean
}>(), {
    canSubmitExtra: true,
    processing: false,
    showSaveDraft: true,
})

defineEmits<{ discard: []; saveDraft: []; submit: [] }>()

const sectionsCompleteCount = computed(() => props.completion.sectionsComplete.size)
const canSubmit = computed(() => props.completion.pct >= 50 && props.canSubmitExtra && !props.processing)
</script>

<template>
    <div class="uf-footer-bar">
        <div class="uf-footer-left">
            <span class="uf-footer-progress">
                {{ completion.pct }}% completo · {{ sectionsCompleteCount }} de {{ totalSections }} secciones
            </span>
            <span class="uf-footer-sep" />
            <UserFormAutosave :autosave="autosave" />
        </div>

        <div class="uf-footer-right">
            <button type="button" class="uf-btn ghost" @click="$emit('discard')">
                Descartar
            </button>
            <button v-if="showSaveDraft" type="button" class="uf-btn secondary" @click="$emit('saveDraft')">
                Guardar borrador
            </button>
            <button
                type="button"
                class="uf-btn primary"
                :disabled="!canSubmit"
                @click="$emit('submit')"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ processing ? 'Creando usuario…' : 'Crear usuario' }}
            </button>
        </div>
    </div>
</template>
