<script setup lang="ts">
import { computed } from 'vue'
import type { TabDef } from '@/types/userFormCatalogs'

const props = defineProps<{
    tabs: TabDef[]
    modelValue: string
    completion: { sectionsComplete: Set<number>; sectionsPartial: Set<number> }
}>()

defineEmits<{ 'update:modelValue': [string] }>()

function tabState(tb: TabDef): { complete: boolean; hasPartial: boolean } {
    const complete = tb.sections.every(n => props.completion.sectionsComplete.has(n))
    const hasPartial = !complete && tb.sections.some(
        n => props.completion.sectionsComplete.has(n) || props.completion.sectionsPartial.has(n),
    )
    return { complete, hasPartial }
}

function padIdx(i: number): string {
    return String(i + 1).padStart(2, '0')
}
</script>

<template>
    <div class="uf-tabs-wrap">
        <div class="uf-tabs" role="tablist">
            <button
                v-for="(tb, i) in tabs"
                :key="tb.key"
                type="button"
                role="tab"
                class="uf-tab"
                :class="{
                    active: modelValue === tb.key,
                    complete: tabState(tb).complete,
                }"
                :aria-selected="modelValue === tb.key"
                @click="$emit('update:modelValue', tb.key)"
            >
                <span class="uf-tab-idx">{{ padIdx(i) }}</span>
                {{ tb.label }}
                <span v-if="tabState(tb).hasPartial" class="uf-tab-warn" />
            </button>
        </div>
    </div>
</template>
