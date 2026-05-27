<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    completion: { pct: number; sectionsComplete: Set<number> }
    totalSections: number
    autosave: { status: string; when: Date | null }
}>()

defineEmits<{ discard: []; saveDraft: []; submit: [] }>()

const sectionsCompleteCount = computed(() => props.completion.sectionsComplete.size)
const canSubmit = computed(() => props.completion.pct >= 50)
</script>

<template>
    <div class="uf-footer-bar">
        <div class="uf-footer-left">
            <span class="uf-footer-progress">
                {{ completion.pct }}% completo · {{ sectionsCompleteCount }} de {{ totalSections }} secciones
            </span>
            <span class="uf-footer-sep" />
            <span class="uf-autosave" :class="autosave.status">
                <span class="uf-autosave-dot" />
                <span v-if="autosave.status === 'saving'">Guardando…</span>
                <span v-else-if="autosave.status === 'saved'">Guardado</span>
                <span v-else-if="autosave.status === 'error'" :title="autosave.message">Error al guardar</span>
                <span v-else>Sin cambios</span>
            </span>
        </div>

        <div class="uf-footer-right">
            <button type="button" class="uf-btn ghost" @click="$emit('discard')">
                Descartar
            </button>
            <button type="button" class="uf-btn secondary" @click="$emit('saveDraft')">
                Guardar borrador
            </button>
            <button
                type="button"
                class="uf-btn primary"
                :disabled="!canSubmit"
                @click="$emit('submit')"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Crear usuario
            </button>
        </div>
    </div>
</template>
