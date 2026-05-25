<script setup lang="ts">
import type { SectionStatus } from '@/composables/forms/useUserFormPage'

defineProps<{
    num: string
    title: string
    sub?: string
    note?: string
    roleTag?: string | null
    comfy?: boolean
    status: SectionStatus
    saved?: { at: Date } | null
    readonly?: boolean
    sectionId: number
    isSaving?: boolean
    sectionErrors?: Record<string, string>
}>()

defineEmits<{ save: []; enterEdit: []; cancel: [] }>()
</script>

<template>
    <section
        :id="'sec-' + sectionId"
        class="uf-section"
        :class="{
            complete: status === 'complete',
            partial: status === 'partial',
            editing: status === 'editing',
            readonly,
        }"
    >
        <div class="uf-section-head">
            <div class="uf-section-head-left">
                <span class="uf-section-num">{{ num }}</span>
                <div class="uf-section-title-wrap">
                    <h2 class="uf-section-title">{{ title }}</h2>
                    <p v-if="sub" class="uf-section-sub">{{ sub }}</p>
                </div>
            </div>
            <div class="uf-section-head-right">
                <span v-if="roleTag" class="uf-section-role-tag">{{ roleTag }}</span>
                <span v-if="saved" class="uf-section-saved">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Guardado
                </span>
                <button v-if="readonly" type="button" class="uf-btn ghost sm" @click="$emit('enterEdit')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Editar
                </button>
            </div>
        </div>

        <div v-if="note" class="uf-section-note">{{ note }}</div>

        <div class="uf-section-body" :class="{ comfy }">
            <slot />
        </div>

        <div
            v-if="sectionErrors && Object.keys(sectionErrors).length"
            class="uf-section-errors"
        >
            <p v-for="(msg, field) in sectionErrors" :key="field" class="uf-section-error">
                {{ msg }}
            </p>
        </div>

        <div v-if="!readonly" class="uf-section-foot">
            <span class="uf-section-status-text">
                <template v-if="status === 'complete'">Sección completa</template>
                <template v-else-if="status === 'partial'">En progreso</template>
                <template v-else-if="status === 'editing'">Editando</template>
                <template v-else>Sin completar</template>
            </span>
            <div class="uf-section-foot-actions">
                <button
                    v-if="status === 'editing'"
                    type="button"
                    class="uf-btn ghost sm"
                    @click="$emit('cancel')"
                >
                    Descartar
                </button>
                <button
                    type="button"
                    class="uf-btn primary sm"
                    :disabled="isSaving"
                    @click="$emit('save')"
                >
                    <span v-if="isSaving" class="uf-btn-spinner" aria-hidden="true" />
                    {{ isSaving ? 'Guardando…' : 'Guardar sección' }}
                </button>
            </div>
        </div>
    </section>
</template>
