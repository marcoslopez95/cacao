<script setup lang="ts">
import { UF_SECTIONS } from '@/types/userFormCatalogs'

defineProps<{
    sections: number[]
    activeSection: number | null
    completion: { sectionsComplete: Set<number>; sectionsPartial: Set<number> }
    savedSections: Set<number>
    editingSections: Set<number>
}>()

const emit = defineEmits<{ scrollTo: [number] }>()
</script>

<template>
    <nav class="uf-side-nav">
        <p class="uf-side-nav-title">En esta pestaña</p>
        <button
            v-for="n in sections"
            :key="n"
            type="button"
            class="uf-side-link"
            :class="{
                active: activeSection === n,
                complete: completion.sectionsComplete.has(n),
                partial: completion.sectionsPartial.has(n) && !completion.sectionsComplete.has(n),
                editing: editingSections.has(n),
            }"
            @click="emit('scrollTo', n)"
        >
            <span class="uf-side-status" />
            {{ UF_SECTIONS[n]?.title ?? `Sección ${n}` }}
        </button>
    </nav>
</template>
